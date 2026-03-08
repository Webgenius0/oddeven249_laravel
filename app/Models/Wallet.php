<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'total_balance',
        'available_balance',
        'held_balance',
        'total_earned',
        'total_withdrawn',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'total_balance'     => 'decimal:2',
        'available_balance' => 'decimal:2',
        'held_balance'      => 'decimal:2',
        'total_earned'      => 'decimal:2',
        'total_withdrawn'   => 'decimal:2',
        'is_active'         => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /** Available balance is sufficient? */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->available_balance >= $amount;
    }
}
