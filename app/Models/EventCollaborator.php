<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCollaborator extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'status',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'user_id' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function collaborator()
    {
        return $this->belongsTo(User::class, 'collaborator_id');
    }
}
