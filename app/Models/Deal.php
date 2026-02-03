<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function deliveries()
    {
        return $this->hasMany(DealDelivery::class);
    }

    public function extensions()
    {
        return $this->hasMany(DealExtension::class);
    }
}
