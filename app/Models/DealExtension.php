<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealExtension extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
