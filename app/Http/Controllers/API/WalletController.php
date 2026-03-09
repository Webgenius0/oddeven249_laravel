<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    use ApiResponse;

    protected $walletService;
    protected $stripeService;

    public function __construct(WalletService $walletService, StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
        $this->walletService  = $walletService;
    }
    public function summary()
    {
        try {
            $data = $this->walletService->getWalletSummary(auth()->user());
            return $this->success($data, 'Wallet summary fetched.');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function transactions(Request $request)
    {
        try {
            $data = $this->walletService->getTransactionHistory(
                auth()->user(),
                $request->get('per_page', 15)
            );
            return $this->success($data, 'Transactions fetched.');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function createTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        try {
            $result = $this->stripeService->createTopupSession(
                auth()->user(),
                (float) $request->amount
            );
            return $this->success($result, 'Checkout session created.');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), $e->getCode() ?: 500);
        }
    }
    public function connectBank()
    {
        try {
            $url = $this->stripeService->createConnectOnboardingLink(auth()->user());
            return $this->success(['onboarding_url' => $url], 'Onboarding link generated.');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function connectStatus()
    {
        try {
            $enabled = $this->stripeService->checkConnectStatus(auth()->user());
            return $this->success(['connected' => $enabled], 'Status fetched.');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $user = auth()->user();

        return DB::transaction(function () use ($user, $request) {
            try {
                $transfer = $this->stripeService->processWithdrawal($user, (float) $request->amount);
                $this->walletService->withdraw(
                    user:        $user,
                    amount:      (float) $request->amount,
                    description: 'Withdrawal to bank account'
                );
                \App\Models\WithdrawalRequest::create([
                    'user_id'            => $user->id,
                    'amount'             => $request->amount,
                    'currency'           => 'USD',
                    'stripe_transfer_id' => $transfer['transfer_id'],
                    'status'             => $transfer['status'],
                ]);

                return $this->success(null, 'Withdrawal processed successfully.');
            } catch (\Exception $e) {
                return $this->error(null, $e->getMessage(), $e->getCode() ?: 500);
            }
        });
    }
}
