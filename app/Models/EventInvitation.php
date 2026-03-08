<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventInvitation extends Model
{
    protected $fillable = [
        'event_id', 'invited_by', 'invited_user_id',
        'event_ticket_id', 'message','payment_status', 'requested_amount','status',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }
    public function ticket()
    {
        return $this->belongsTo(EventTicket::class, 'event_ticket_id');
    }
}
