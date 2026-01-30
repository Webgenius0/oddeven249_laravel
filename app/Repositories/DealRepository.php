<?php

namespace App\Repositories;

use App\Models\Deal;

class DealRepository
{
    public function create(array $data)
    {
        return Deal::create($data);
    }

    public function getAllForUser($userId, $role, $status = null)
    {
        $column = ($role === 'influencer') ? 'influencer_id' : 'advertiser_id';
        $query = Deal::where($column, $userId)
                     ->with(['influencer', 'advertiser']);
        if ($status) {
            $query->where('status', $status);
        }
        return $query->latest()->get();
    }
    public function updateOrCreateRating(array $data)
    {
        return \App\Models\DealRating::updateOrCreate(
            ['deal_id' => $data['deal_id'], 'rated_by' => $data['rated_by']],
            [
                'rated_to' => $data['rated_to'],
                'rating'   => $data['rating'],
                'message'  => $data['message'] ?? null,
            ]
        );
    }
    public function getRatingWithDetails($dealId, $targetUserId = null)
    {
        return \App\Models\DealRating::where('deal_id', $dealId)
          ->where('rated_to', $targetUserId)
          ->with(['ratedBy:id,name', 'deal:id,campaign_name'])
          ->first();
    }
}
