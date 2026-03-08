<?php

// app/Services/DisputeService.php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealDispute;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class DisputeService
{
    public function __construct(protected WalletService $walletService)
    {
    }

    // ─── Buyer raises dispute ───────────────────────────────────────
    public function raiseDispute(User $user, array $data, $effectiveId = null): DealDispute
    {
        $myId = (int) ($effectiveId ?: $user->id);
        $deal = Deal::findOrFail($data['deal_id']);

        if ($deal->buyer_id !== $myId) {
            throw new Exception('Only the buyer can raise a dispute.', 403);
        }

        if (!in_array($deal->status, ['active', 'delivered'])) {
            throw new Exception('Dispute can only be raised on active or delivered deals.', 422);
        }

        $existing = DealDispute::where('deal_id', $deal->id)
            ->whereIn('status', ['open', 'under_review'])
            ->exists();

        if ($existing) {
            throw new Exception('A dispute is already open for this deal.', 409);
        }

        $attachmentPath = isset($data['attachment'])
            ? uploadImage($data['attachment'], 'disputes')
            : null;

        return DB::transaction(function () use ($deal, $myId, $user, $data, $attachmentPath) {
            $dispute = DealDispute::create([
                'deal_id'    => $deal->id,
                'raised_by'  => $myId,
                'reason'     => $data['reason'],
                'attachment' => $attachmentPath,
                'status'     => 'open',
            ]);

            // Deal freeze করো
            $deal->update(['status' => 'disputed']);

            return $dispute;
        });
    }

    // ─── Admin resolves dispute ─────────────────────────────────────
    public function resolveDispute(User $admin, array $data): DealDispute
    {
        $dispute = DealDispute::with('deal')->findOrFail($data['dispute_id']);
        $deal    = $dispute->deal;

        if ($dispute->status === 'resolved') {
            throw new Exception('This dispute is already resolved.', 400);
        }

        if (!in_array($data['resolution'], ['refund_buyer', 'release_seller'])) {
            throw new Exception('Invalid resolution type.', 422);
        }

        return DB::transaction(function () use ($admin, $dispute, $deal, $data) {
            $buyer  = User::find($deal->buyer_id);
            $seller = User::find($deal->seller_id);

            if ($data['resolution'] === 'refund_buyer') {
                // Held amount → buyer কে ফেরত
                $this->walletService->release(
                    user:        $buyer,
                    amount:      $deal->amount,
                    sourceType:  'dispute',
                    sourceId:    $dispute->id,
                    description: "Refund — Dispute #{$dispute->id} resolved in buyer's favor"
                );

                $deal->update(['status' => 'refunded']);

            } else {
                // Held amount → seller কে দাও (commission + tax সহ)
                $this->walletService->settleDeal(
                    buyer:      $buyer,
                    seller:     $seller,
                    dealAmount: (float) $deal->amount,
                    dealId:     $deal->id
                );

                $deal->update(['status' => 'completed']);
            }

            $dispute->update([
                'status'      => 'resolved',
                'resolution'  => $data['resolution'],
                'admin_note'  => $data['admin_note'] ?? null,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            return $dispute->fresh();
        });
    }

    // ─── Admin marks as under_review ───────────────────────────────
    public function markUnderReview(User $admin, int $disputeId): DealDispute
    {
        $dispute = DealDispute::findOrFail($disputeId);

        if ($dispute->status !== 'open') {
            throw new Exception('Only open disputes can be marked under review.', 422);
        }

        $dispute->update(['status' => 'under_review']);
        return $dispute;
    }

    public function getDisputeById(int $id): DealDispute
    {
        return DealDispute::with(['deal.buyer', 'deal.seller', 'raisedBy', 'resolvedBy'])
            ->findOrFail($id);
    }

    public function getAllDisputes(string $status = null)
    {
        return DealDispute::with(['deal:id,campaign_name,amount', 'raisedBy:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15);
    }
}
