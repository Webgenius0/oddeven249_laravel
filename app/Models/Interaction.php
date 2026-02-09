<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interaction extends Model
{
    protected $fillable = [
        'user_id', 'target_id', 'target_type',
        'interaction_type', 'ip_address', 'user_agent'
    ];
    public function target()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
