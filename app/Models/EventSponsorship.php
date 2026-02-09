<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSponsorship extends Model
{
    protected $fillable = [
        'event_id',
        'sponsor_id',
        'amount',
        'payment_status',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'sponsor_id' => 'integer',
        'amount' => 'decimal:2',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }
}
