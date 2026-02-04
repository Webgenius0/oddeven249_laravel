<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Request;

class InteractionService
{
    public function toggleInteraction($user, $targetId, $targetType, $interactionType)
    {
        // Morph map theke Model class ber kora (e.g., 'portfolio' -> App\Models\Portfolio)
        $modelClass = Relation::getMorphedModel($targetType);

        if (!$modelClass) {
            throw new \Exception("Invalid target type provided.", 400);
        }

        $model = $modelClass::findOrFail($targetId);

        // Like toggle logic
        if ($interactionType === 'like' && $user) {
            $existing = $model->interactions()
                ->where('user_id', $user->id)
                ->where('interaction_type', 'like')
                ->first();

            if ($existing) {
                $existing->delete();
                return ['status' => 'removed', 'message' => 'Unliked successfully'];
            }
        }

        // Interaction record create
        $model->interactions()->create([
            'user_id'          => $user?->id,
            'interaction_type' => $interactionType,
            'ip_address'       => request()->ip(),
            'user_agent'       => request()->userAgent(),
        ]);

        return ['status' => 'added', 'message' => ucfirst($interactionType) . ' recorded'];
    }
}
