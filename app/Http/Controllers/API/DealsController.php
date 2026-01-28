<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DealService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DealsController extends Controller
{
    use ApiResponse;

    protected $dealService;

    public function __construct(DealService $dealService)
    {
        $this->dealService = $dealService;
    }

    public function index()
    {
        $deals = $this->dealService->getUserDeals(Auth::user());
        return $this->success($deals, 'Deals retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'campaign_name' => 'required|string|max:255',
            'amount'        => 'required|numeric',
            'description'   => 'nullable|string',
            'valid_until'   => 'required|date',
            'duration'      => 'required|string',
        ];

        if ($user->isInfluencer()) {
            $rules['advertiser_id'] = 'required|exists:users,id';
        } else {
            $rules['influencer_id'] = 'required|exists:users,id';
        }

        $validated = $request->validate($rules);

        try {
            $deal = $this->dealService->storeDeal($user, $validated);
            return $this->success($deal, 'Deal request sent successfully!', 200);

        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 422);
        }
    }
}
