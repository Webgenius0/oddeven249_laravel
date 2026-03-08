<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'creator_id' => 'integer',
        'entry_fee' => 'decimal:2',
        'is_published' => 'boolean',
        'date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function event_sponsorships()
    {
        return $this->hasMany(EventSponsorship::class);
    }

    public function event_collaborators()
    {
        return $this->belongsToMany(User::class, 'event_collaborators')
            ->withPivot('status')
            ->withTimestamps();
    }
    public function event_participants()
    {
        return $this->belongsToMany(User::class, 'event_participants', 'event_id', 'participant_id')
            ->withPivot('payment_status')
            ->withTimestamps();
    }
    public function getTotalParticipantsAttribute()
    {
        return $this->event_participants()->count();
    }

    public function getTimeLeftAttribute()
    {
        if ($this->date->isPast()) {
            return "Event Ended";
        }
        return $this->date->diffForHumans(['parts' => 2]); // এটি '2 days from now' বা 'in 5 hours' রিটার্ন করবে
    }
    public function tickets()
    {
        return $this->hasMany(EventTicket::class);
    }
}
