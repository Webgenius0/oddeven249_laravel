<?php

namespace App\Repositories;

use App\Models\Deal;

class DealRepository
{
    public function create(array $data)
    {
        return Deal::create($data);
    }


    public function getAllForUser($userId, $status = null)
    {
        $query = Deal::where(function ($q) use ($userId) {
            $q->where('buyer_id', $userId)
              ->orWhere('seller_id', $userId);
        })
        ->with(['buyer', 'seller']); 

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
        $query = \App\Models\DealRating::where('deal_id', $dealId);

        if ($targetUserId) {
            $query->where('rated_to', $targetUserId);
        }

        return $query->with(['ratedBy:id,name', 'deal:id,campaign_name'])
                     ->first();
    }
}
