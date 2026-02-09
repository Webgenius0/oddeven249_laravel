<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\User;
use App\Repositories\DealRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DealService
{
    protected $dealRepository;

    public function __construct(DealRepository $dealRepository)
    {
        $this->dealRepository = $dealRepository;
    }

    public function storeDeal($user, array $data)
    {
        $buyerId = $user->parent_id ? $user->parent_id : $user->id;
        $targetId = $data['target_id'];

        if ($buyerId == $targetId) {
            throw new Exception("You cannot create a deal with yourself!");
        }
        $dealData = [
            'campaign_name' => $data['campaign_name'],
            'amount'        => $data['amount'],
            'description'   => $data['description'] ?? null,
            'duration'      => $data['duration'],
            'valid_until'   => Carbon::parse($data['valid_until'])->format('Y-m-d H:i:s'),
            'buyer_id'      => $buyerId,
            'seller_id'     => $targetId,
            'requested_by'  => $user->id,
            'status'        => 'pending',
        ];

        return $this->dealRepository->create($dealData);
    }

    public function getUserDeals($user, $status = null)
    {

        $myId = $user->parent_id ? $user->parent_id : $user->id;
        return $this->dealRepository->getAllForUser($myId, $status);
    }

    public function getDealById($id)
    {
        return Deal::with(['buyer', 'seller'])->find($id);
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
            throw new Exception("Deal not found.");
        }

        $myId = $user->isBusinessManager() ? $user->parent_id : $user->id;

        if ($deal->buyer_id !== $myId && $deal->seller_id !== $myId) {
            throw new Exception("Unauthorized to rate this deal.");
        }

        if ($deal->status !== 'completed') {
            throw new Exception("You can only rate completed deals.");
        }

        $data['rated_by'] = $myId;
        $data['rated_to'] = ($myId === $deal->buyer_id) ? $deal->seller_id : $deal->buyer_id;

        return $this->dealRepository->updateOrCreateRating($data);
    }

    public function getRatingByDealId($dealId, $userId)
    {
        return \App\Models\DealRating::where('deal_id', $dealId)
            ->where(function ($query) use ($userId) {
                $query->where('rated_by', $userId)
                      ->orWhere('rated_to', $userId);
            })
            ->with(['deal', 'ratedBy'])
            ->first();
    }

    public function handleDeliverySubmission($user, $data)
    {

        $deal = $this->getDealById($data['deal_id']);
        if (!$deal) {
            throw new Exception('Deal not found.', 404);
        }

        $myId = $user->isBusinessManager() ? $user->parent_id : $user->id;


        $expectedDelivererId = ($deal->requested_by === $deal->buyer_id) ? $deal->seller_id : $deal->buyer_id;

        if ($myId !== $expectedDelivererId) {
            throw new Exception('You are not authorized to submit delivery for this deal.', 403);
        }

        if ($deal->status !== 'active') {
            throw new Exception('Delivery can only be submitted for active deals.', 422);
        }

        $attachmentPath = isset($data['attachment']) ? uploadImage($data['attachment'], 'deliveries') : null;

        return DB::transaction(function () use ($deal, $user, $data, $attachmentPath) {
            $delivery = \App\Models\DealDelivery::create([
                'deal_id'    => $deal->id,
                'sender_id'  => $user->id,
                'message'    => $data['delivery_message'],
                'attachment' => $attachmentPath,
            ]);

            $deal->update(['status' => 'delivered']);
            return $delivery;
        });
    }

    public function handleExtensionRequest($user, $data)
    {
        $deal = $this->getDealById($data['deal_id']);
        $myId = $user->isBusinessManager() ? $user->parent_id : $user->id;

        if (!$deal || ($deal->seller_id !== $myId && $deal->buyer_id !== $myId)) {
            throw new Exception('Unauthorized access to this deal.', 403);
        }

        return \App\Models\DealExtension::create([
            'deal_id'      => $deal->id,
            'requested_by' => $user->id,
            'message'      => $data['extension_message'],
            'new_date'     => $data['extension_date'],
            'new_time'     => $data['extension_time'],
            'status'       => 'pending',
        ]);
    }
    public function handleExtensionAction($user, $data)
    {
        $extension = \App\Models\DealExtension::findOrFail($data['extension_id']);
        $deal = $extension->deal;
        $myId = $user->isBusinessManager() ? $user->parent_id : $user->id;

        if ($extension->requested_by === $user->id) {
            throw new Exception('You cannot approve your own extension request.', 403);
        }

        if ($deal->seller_id !== $myId && $deal->buyer_id !== $myId) {
            throw new Exception('Unauthorized action.', 403);
        }

        return DB::transaction(function () use ($extension, $deal, $data) {
            if ($data['status'] === 'approved') {
                $extension->update(['status' => 'approved']);
                $deal->update(['valid_until' => $extension->new_date . ' ' . $extension->new_time]);
            } else {
                $extension->update(['status' => 'rejected']);
            }
            return $extension;
        });
    }

    public function getAllExtensionsForUser($user)
    {
        $myId = $user->isBusinessManager() ? $user->parent_id : $user->id;
        return \App\Models\DealExtension::whereHas('deal', function ($query) use ($myId) {
            $query->where('seller_id', $myId)->orWhere('buyer_id', $myId);
        })
        ->with(['deal:id,campaign_name', 'requester:id,name'])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    public function handleDeliveryAction($user, $data)
    {
        $delivery = \App\Models\DealDelivery::findOrFail($data['delivery_id']);
        $deal = $delivery->deal;

        $myId = $user->isBusinessManager() ? $user->parent_id : $user->id;

        if ($delivery->sender_id === $user->id) {
            throw new Exception('You cannot take action on your own delivery.', 403);
        }

        if ($deal->buyer_id !== $myId && $deal->seller_id !== $myId) {
            throw new Exception('Unauthorized access to this deal delivery.', 403);
        }

        return DB::transaction(function () use ($delivery, $deal, $data) {
            if ($data['status'] === 'accepted') {
                $delivery->update(['status' => 'accepted']);
                $deal->update(['status' => 'completed']);
            } else {
                $delivery->update(['status' => 'rejected']);
                $deal->update(['status' => 'active']);
            }
            return $delivery;
        });
    }
}
