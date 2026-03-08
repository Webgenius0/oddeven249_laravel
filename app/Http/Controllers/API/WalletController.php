<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * Wallet summary — balance দেখো
     */
    public function index()
    {
        try {
            $summary = $this->walletService->getWalletSummary(Auth::user());
            return $this->success($summary, 'Wallet retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    /**
     * Transaction history
     */
    public function transactions(Request $request)
    {
        try {
            $transactions = $this->walletService->getTransactionHistory(
                Auth::user(),
                $request->input('per_page', 15)
            );
            return $this->success($transactions, 'Transactions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    /**
     * Withdrawal request
     */
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $transaction = $this->walletService->withdraw(
                Auth::user(),
                $request->amount,
                'Withdrawal request'
            );
            return $this->success($transaction, 'Withdrawal request submitted successfully');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 422);
        }
    }
}
