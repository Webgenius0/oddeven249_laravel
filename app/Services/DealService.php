<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\DealRepository;
use Carbon\Carbon;
use Exception;

class DealService
{
    protected $dealRepository;

    public function __construct(DealRepository $dealRepository)
    {
        $this->dealRepository = $dealRepository;
    }

    public function storeDeal($user, array $data)
    {
        $targetId = $user->isInfluencer() ? $data['advertiser_id'] : $data['influencer_id'];
        $targetUser = User::find($targetId);

        if ($user->id == $targetId) {
            throw new Exception("You cannot create a deal with yourself!");
        }

        if ($user->isInfluencer() && !$targetUser->isAdvertiser()) {
            throw new Exception("You can only send deals to Advertisers.");
        }

        if ($user->isAdvertiser() && !$targetUser->isInfluencer()) {
            throw new Exception("You can only send deals to Influencers.");
        }


        $data['requested_by'] = $user->id;
        $data['valid_until'] = Carbon::parse($data['valid_until'])->format('Y-m-d H:i:s');

        if ($user->isInfluencer()) {
            $data['influencer_id'] = $user->id;
        } else {
            $data['advertiser_id'] = $user->id;
        }

        return $this->dealRepository->create($data);
    }

    public function getUserDeals($user)
    {
        return $this->dealRepository->getAllForUser($user->id, $user->role);
    }
}
