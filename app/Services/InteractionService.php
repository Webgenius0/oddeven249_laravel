<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;

class InteractionService
{
    public function toggleInteraction($user, $targetId, $targetType, $interactionType)
    {
        $modelClass = Relation::getMorphedModel($targetType);

        if (!$modelClass) {
            throw new \Exception("Invalid target type provided.", 400);
        }

        $model = $modelClass::findOrFail($targetId);

        // ── Like toggle ───────────────────────────────────────────────
        if ($interactionType === 'like' && $user) {
            $existing = $model->interactions()
                ->where('user_id', $user->id)
                ->where('interaction_type', 'like')
                ->first();

            if ($existing) {
                $existing->delete();
                return ['status' => 'removed', 'message' => 'Unliked successfully'];
            }

            $model->interactions()->create([
                'user_id'          => $user->id,
                'interaction_type' => 'like',
                'ip_address'       => request()->ip(),
                'user_agent'       => request()->userAgent(),
            ]);

            return ['status' => 'added', 'message' => 'Liked successfully'];
        }

        // ── View deduplication ────────────────────────────────────────
        if ($interactionType === 'view') {
            if ($this->isDuplicateView($user, $targetType, $targetId)) {
                return ['status' => 'skipped', 'message' => 'View already recorded'];
            }

            $this->markViewSeen($user, $targetType, $targetId);
        }

        // ── Record interaction ────────────────────────────────────────
        $model->interactions()->create([
            'user_id'          => $user?->id,
            'interaction_type' => $interactionType,
            'ip_address'       => request()->ip(),
            'user_agent'       => request()->userAgent(),
        ]);

        return ['status' => 'added', 'message' => ucfirst($interactionType) . ' recorded'];
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE — View deduplication helpers
    // ─────────────────────────────────────────────────────────────────

    private function isDuplicateView($user, string $targetType, int $targetId): bool
    {
        $cacheKey = $this->buildViewCacheKey($user, $targetType, $targetId);
        return Cache::has($cacheKey);
    }

    private function markViewSeen($user, string $targetType, int $targetId): void
    {
        $cacheKey = $this->buildViewCacheKey($user, $targetType, $targetId);
        Cache::put($cacheKey, true, now()->addHours(24));
    }

    private function buildViewCacheKey($user, string $targetType, int $targetId): string
    {
        if ($user) {
            return "view:{$targetType}:{$targetId}:user:{$user->id}";
        }
        $fingerprint = md5(request()->ip() . '|' . request()->userAgent());
        return "view:{$targetType}:{$targetId}:guest:{$fingerprint}";
    }
}
