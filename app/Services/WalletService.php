<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function createWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'total_balance'     => 0,
                'available_balance' => 0,
                'held_balance'      => 0,
                'total_earned'      => 0,
                'total_withdrawn'   => 0,
                'currency'          => 'BDT',
                'is_active'         => true,
            ]
        );
    }

    public function getOrCreateWallet(User $user): Wallet
    {
        return $user->wallet ?? $this->createWallet($user);
    }


    public function deposit(
        User $user,
        float $amount,
        string $type = 'deposit',
        string $sourceType = null,
        int $sourceId = null,
        string $description = null
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $type, $sourceType, $sourceId, $description) {
            $wallet = $this->getFreshLockedWallet($user);

            $balanceBefore = $wallet->available_balance;

            $wallet->increment('available_balance', $amount);
            $wallet->increment('total_balance', $amount);

            $earningTypes = ['deposit', 'deal_payment', 'contest_prize', 'event_ticket_payment', ];
            if (in_array($type, $earningTypes)) {
                $wallet->increment('total_earned', $amount);
            }

            return $this->logTransaction($wallet, [
                'type'           => $type,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore + $amount,
                'source_type'    => $sourceType,
                'source_id'      => $sourceId,
                'description'    => $description ?? ucfirst(str_replace('_', ' ', $type)),
                'status'         => 'completed',
            ]);
        });
    }
    public function hold(
        User $user,
        float $amount,
        string $sourceType = null,
        int $sourceId = null,
        string $description = 'Amount held in escrow'
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description) {
            $wallet = $this->getFreshLockedWallet($user);

            if ($wallet->available_balance < $amount) {
                throw new \Exception(
                    "Insufficient balance. Available: {$wallet->available_balance}, Required: {$amount}"
                );
            }

            $balanceBefore = $wallet->available_balance;

            $wallet->decrement('available_balance', $amount);
            $wallet->increment('held_balance', $amount);

            return $this->logTransaction($wallet, [
                'type'           => 'hold',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore - $amount,
                'source_type'    => $sourceType,
                'source_id'      => $sourceId,
                'description'    => $description,
                'status'         => 'completed',
            ]);
        });
    }
    public function release(
        User $user,
        float $amount,
        string $sourceType = null,
        int $sourceId = null,
        string $description = 'Escrow amount released'
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description) {
            // 🔒 lockForUpdate
            $wallet = $this->getFreshLockedWallet($user);

            if ($wallet->held_balance < $amount) {
                throw new \Exception(
                    "Insufficient held balance to release. Held: {$wallet->held_balance}, Required: {$amount}"
                );
            }

            $balanceBefore = $wallet->available_balance;

            $wallet->decrement('held_balance', $amount);
            $wallet->increment('available_balance', $amount);
            return $this->logTransaction($wallet, [
                'type'           => 'release',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore + $amount,
                'source_type'    => $sourceType,
                'source_id'      => $sourceId,
                'description'    => $description,
                'status'         => 'completed',
            ]);
        });
    }
    public function withdraw(
        User $user,
        float $amount,
        string $description = 'Withdrawal request'
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $description) {

            $wallet = $this->getFreshLockedWallet($user);
            if ($wallet->available_balance < $amount) {
                throw new \Exception(
                    "Insufficient available balance. Available: {$wallet->available_balance}, Required: {$amount}"
                );
            }

            $balanceBefore = $wallet->available_balance;

            $wallet->decrement('available_balance', $amount);
            $wallet->decrement('total_balance', $amount);
            $wallet->increment('total_withdrawn', $amount);

            return $this->logTransaction($wallet, [
                'type'           => 'withdrawal',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore - $amount,
                'description'    => $description,
                'status'         => 'pending',
            ]);
        });
    }
    public function settleDeal(
        User $buyer,
        User $seller,
        float $dealAmount,
        int $dealId
    ): array {
        return DB::transaction(function () use ($buyer, $seller, $dealAmount, $dealId) {
            $settings       = $this->getPlatformSettings();
            $commissionRate = $settings->platform_commission / 100;
            $taxRate        = $settings->tax_rate / 100;

            $commissionAmount = round($dealAmount * $commissionRate, 2);
            $taxAmount        = round($dealAmount * $taxRate, 2);
            $sellerReceives   = $dealAmount - ($commissionAmount + $taxAmount);

            $buyerWallet = $this->getFreshLockedWallet($buyer);

            if ($buyerWallet->held_balance < $dealAmount) {
                throw new \Exception("Insufficient held balance for settlement.");
            }

            $heldBefore = $buyerWallet->held_balance;

            $buyerWallet->decrement('held_balance', $dealAmount);
            $buyerWallet->decrement('total_balance', $dealAmount);
            $this->logTransaction($buyerWallet, [
                'type'              => 'deal_payment',
                'amount'            => $dealAmount,
                'balance_before'    => $heldBefore,
                'balance_after'     => $heldBefore - $dealAmount,
                'source_type'       => 'deal',
                'source_id'         => $dealId,
                'commission_amount' => $commissionAmount,
                'tax_amount'        => $taxAmount,
                'description'       => "Payment for Deal #{$dealId}",
                'status'            => 'completed',
            ]);

            $this->deposit(
                $seller,
                $sellerReceives,
                'deal_payment',
                'deal',
                $dealId,
                "Received for Deal #{$dealId} (Tax & Comm. deducted)"
            );

            $admin = $this->getCachedAdmin();
            if ($admin) {
                $this->deposit($admin, $commissionAmount, 'commission_deduction', 'deal', $dealId, "Comm. from Deal #{$dealId}");

                if ($taxAmount > 0) {
                    $this->deposit($admin, $taxAmount, 'tax_deduction', 'deal', $dealId, "Tax from Deal #{$dealId}");
                }
            }

            return [
                'deal_amount'     => $dealAmount,
                'commission'      => $commissionAmount,
                'tax'             => $taxAmount,
                'seller_receives' => $sellerReceives,
            ];
        });
    }
    public function awardContestPrize(
        User $winner,
        float $prizeAmount,
        int $contestId
    ): WalletTransaction {
        return $this->deposit(
            $winner,
            $prizeAmount,
            'contest_prize',
            'contest',
            $contestId,
            "Contest #{$contestId} prize money"
        );
    }
    public function getTransactionHistory(User $user, int $perPage = 15)
    {
        $wallet = $this->getOrCreateWallet($user);
        return $wallet->transactions()->orderByDesc('created_at')->paginate($perPage);
    }

    public function getWalletSummary(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user);

        return [
            'currency'          => $wallet->currency,
            'total_balance'     => $wallet->total_balance,
            'available_balance' => $wallet->available_balance,
            'held_balance'      => $wallet->held_balance,
            'total_earned'      => $wallet->total_earned,
            'total_withdrawn'   => $wallet->total_withdrawn,
        ];
    }

    private function getFreshLockedWallet(User $user): Wallet
    {
        $wallet = $this->getOrCreateWallet($user);

        return Wallet::where('id', $wallet->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function getCachedAdmin(): ?User
    {
        return Cache::remember('platform_admin_user', now()->addHour(), function () {
            return User::where('role', 'admin')->first();
        });
    }
    private function logTransaction(Wallet $wallet, array $data): WalletTransaction
    {
        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id'   => $wallet->user_id,
            'reference' => 'TXN-' . strtoupper(Str::random(10)),
            ...$data,
        ]);
    }
    private function getPlatformSettings(): object
    {
        return Cache::remember('system_platform_settings', now()->addHours(24), function () {
            return \App\Models\SystemSetting::first() ?? (object)[
                'platform_commission' => 0,
                'tax_rate' => 0
            ];
        });
    }
}
