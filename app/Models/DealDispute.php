<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealDispute extends Model
{
    protected $fillable = [
        'deal_id', 'raised_by', 'reason', 'attachment',
        'status', 'resolution', 'admin_note', 'resolved_by', 'resolved_at',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function raisedBy()
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
