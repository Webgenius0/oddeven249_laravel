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

    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status');
        $deals = $this->dealService->getUserDeals($user, $status);

        $transformedDeals = $deals->map(function ($deal) use ($user) {
            $partner = $user->isInfluencer() ? $deal->advertiser : $deal->influencer;

            return [
                'id'            => $deal->id,
                'campaign_name' => $deal->campaign_name,
                'status'        => $deal->status,
                'amount'        => $deal->amount,
                'valid_until'   => $deal->valid_until,
                'created_at'   => $deal->created_at,
                'partner'       => [
                    'id'   => $partner->id ?? null,
                    'name' => $partner->name ?? 'N/A',
                    'role' => $partner->role ?? 'N/A',
                ],
            ];
        });

        return $this->success($transformedDeals, 'Deals retrieved successfully');
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
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:deals,id'
        ]);
        $id = $request->id;
        $user = Auth::user();
        $deal = $this->dealService->getDealById($id);
        if (!$deal) {
            return $this->error(null, 'Deal not found', 404);
        }
        if ($deal->advertiser_id !== $user->id && $deal->influencer_id !== $user->id) {
            return $this->error(null, 'Unauthorized access', 403);
        }
        $partner = $user->isInfluencer() ? $deal->advertiser : $deal->influencer;
        $details = [
            'id'            => $deal->id,
            'campaign_name' => $deal->campaign_name,
            'description'   => $deal->description,
            'status'        => $deal->status,
            'amount'        => $deal->amount,
            'duration'      => $deal->duration,
            'valid_until'   => $deal->valid_until,
            'created_at'    => $deal->created_at,
            'partner'       => [
                'id'    => $partner->id ?? null,
                'name'  => $partner->name ?? 'N/A',
                'role'  => $partner->role ?? 'N/A',
                'email' => $partner->email ?? 'N/A',
            ],
        ];
        return $this->success($details, 'Deal details retrieved successfully');
    }
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:deals,id',
            'status' => 'required|in:pending,active,completed,rejected,delivered'
        ]);

        $user = Auth::user();
        $deal = $this->dealService->getDealById($request->id);

        if (!$deal) {
            return $this->error(null, 'Deal not found', 404);
        }
        if ($deal->advertiser_id !== $user->id && $deal->influencer_id !== $user->id) {
            return $this->error(null, 'Unauthorized access', 403);
        }
        if ($request->status === 'active' && $deal->requested_by === $user->id) {
            return $this->error(null, 'You cannot accept your own deal request', 403);
        }

        try {
            $updatedDeal = $this->dealService->updateDealStatus($deal, $request->status);
            return $this->success($updatedDeal, 'Deal status updated successfully');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function rateDeal(Request $request)
    {
        $validated = $request->validate([
            'deal_id' => 'required|exists:deals,id',
            'rating'  => 'required|integer|min:1|max:5',
            'message' => 'nullable|string|max:500',
        ]);

        try {
            $rating = $this->dealService->rateDeal(Auth::user(), $validated);
            return $this->success($rating, 'Rating submitted successfully');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 422);
        }
    }
    public function getRatingDetails(Request $request)
    {
        $request->validate([
            'deal_id' => 'required|exists:deals,id'
        ]);
        $user = Auth::user();
        try {
            $rating = $this->dealService->getRatingByDealId($request->deal_id, $user->id);

            if (!$rating) {
                return $this->error(null, 'No rating found for this deal', 404);
            }

            $data = [
                'id'            => $rating->id,
                'rating'        => $rating->rating,
                'message'       => $rating->message,
                'campaign_name' => $rating->deal->campaign_name ?? 'N/A',
                'rated_by'      => [
                    'id'   => $rating->ratedBy->id ?? null,
                    'name' => $rating->ratedBy->name ?? 'N/A',
                ]
            ];

            return $this->success($data, 'Rating details retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
}
