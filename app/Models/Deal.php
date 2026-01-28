<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function influencer()
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    public function advertiser()
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
