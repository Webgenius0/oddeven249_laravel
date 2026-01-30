<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealRating extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $hidden = ['updated_at'];
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function ratedBy()
    {
        return $this->belongsTo(User::class, 'rated_by');
    }

    public function ratedTo()
    {
        return $this->belongsTo(User::class, 'rated_to');
    }
}
