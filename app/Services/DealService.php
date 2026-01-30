<?php

namespace App\Services;

use App\Models\Deal;
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

    public function getUserDeals($user, $status = null)
    {
        return $this->dealRepository->getAllForUser($user->id, $user->role, $status);
    }
    public function getDealById($id)
    {
        return Deal::with(['advertiser', 'influencer'])->find($id);
    }
    public function updateDealStatus(Deal $deal, string $status)
    {
        $deal->status = $status;
        $deal->save();
        return $deal;
    }
    public function rateDeal($user, array $data)
    {
        $deal = $this->getDealById($data['deal_id']);

        if (!$deal) {
            throw new \Exception("Deal not found.");
        }
        if ($deal->advertiser_id !== $user->id && $deal->influencer_id !== $user->id) {
            throw new \Exception("Unauthorized to rate this deal.");
        }

        if ($deal->status !== 'completed') {
            throw new \Exception("You can only rate completed deals.");
        }
        $data['rated_by'] = $user->id;
        $data['rated_to'] = ($user->id === $deal->advertiser_id) ? $deal->influencer_id : $deal->advertiser_id;

        return $this->dealRepository->updateOrCreateRating($data);
    }
    public function getRatingByDealId($dealId, $authUserId = null)
    {
        return $this->dealRepository->getRatingWithDetails($dealId, $authUserId);
    }
}
