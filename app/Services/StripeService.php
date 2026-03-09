<?php

// app/Services/StripeService.php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTopupRequest;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Exception;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    private function getOrCreateStripeCustomer(User $user): string
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $customer = \Stripe\Customer::create([
            'email'    => $user->email,
            'name'     => $user->name,
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    public function createTopupSession(User $user, float $amount): array
    {
        if ($amount < 10) {
            throw new Exception("Minimum top-up amount is 10.", 422);
        }

        $customerId = $this->getOrCreateStripeCustomer($user);

        $session = Session::create([
            'customer'             => $customerId, // ✅
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => (int) ($amount * 100),
                    'product_data' => ['name' => 'Wallet Top-up'],
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => 'http://oddeven.test' . '/wallet/topup/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => 'http://oddeven.test' . '/wallet/topup/cancel',
            'metadata'    => [
                'user_id' => $user->id,
                'amount'  => $amount,
            ],
        ]);

        WalletTopupRequest::create([
            'user_id'           => $user->id,
            'amount'            => $amount,
            'currency'          => 'USD',
            'stripe_session_id' => $session->id,
            'status'            => 'pending',
        ]);

        return [
            'session_id'   => $session->id,
            'checkout_url' => $session->url,
        ];
    }

    public function handleWebhook(string $payload, string $sigHeader): array
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\UnexpectedValueException $e) {
            throw new Exception("Invalid payload.", 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new Exception("Invalid signature.", 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;
            case 'checkout.session.expired':
                $this->handleCheckoutExpired($event->data->object);
                break;
        }

        return ['status' => 'ok'];
    }

    private function handleCheckoutCompleted($session): void
    {
        $topup = WalletTopupRequest::where('stripe_session_id', $session->id)
            ->where('status', 'pending')
            ->first();

        if (!$topup) {
            return;
        } // Already processed

        DB::transaction(function () use ($topup, $session) {
            $user = User::findOrFail($topup->user_id);

            app(WalletService::class)->deposit(
                user:        $user,
                amount:      $topup->amount,
                type:        'deposit',
                sourceType:  'stripe_topup',
                sourceId:    $topup->id,
                description: "Wallet top-up via Stripe"
            );

            $topup->update([
                'status'                => 'completed',
                'stripe_payment_intent' => $session->payment_intent,
            ]);
        });
    }

    private function handleCheckoutExpired($session): void
    {
        WalletTopupRequest::where('stripe_session_id', $session->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);
    }

    public function createConnectOnboardingLink(User $user): string
    {
        // Already connected?
        if ($user->stripe_connect_id) {
            $connectId = $user->stripe_connect_id;
        } else {
            $account = \Stripe\Account::create([
                'type'  => 'express',
                'email' => $user->email,
                'metadata' => ['user_id' => $user->id],
            ]);

            $user->update(['stripe_connect_id' => $account->id]);
            $connectId = $account->id;
        }
        $link = \Stripe\AccountLink::create([
            'account'     => $connectId,
            'refresh_url' => config('app.frontend_url') . '/wallet/connect/refresh',
            'return_url'  => config('app.frontend_url') . '/wallet/connect/success',
            'type'        => 'account_onboarding',
        ]);

        return $link->url;
    }
    public function checkConnectStatus(User $user): bool
    {
        if (!$user->stripe_connect_id) {
            return false;
        }

        $account = \Stripe\Account::retrieve($user->stripe_connect_id);

        $enabled = $account->charges_enabled && $account->payouts_enabled;

        if ($enabled && !$user->stripe_connect_enabled) {
            $user->update(['stripe_connect_enabled' => true]);
        }

        return $enabled;
    }

    // ─── Auto Withdrawal ─────────────────────────────────────────────
    public function processWithdrawal(User $user, float $amount): array
    {
        if (!$user->stripe_connect_id || !$user->stripe_connect_enabled) {
            throw new Exception("Please connect your bank account first.", 422);
        }

        $transfer = \Stripe\Transfer::create([
            'amount'      => (int) ($amount * 100),
            'currency'    => 'usd',
            'destination' => $user->stripe_connect_id,
            'metadata'    => ['user_id' => $user->id],
        ]);

        return [
            'transfer_id' => $transfer->id,
            'amount'      => $amount,
            'status'      => $transfer->reversed ? 'failed' : 'completed',
        ];
    }
}
