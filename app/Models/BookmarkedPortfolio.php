<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookmarkedPortfolio extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }
}
