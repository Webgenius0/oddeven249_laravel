<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeService $stripeService)
    {
        try {
            $result = $stripeService->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], (int) $e->getCode() ?: 400);
        }
    }
}
