<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function media()
    {
        return $this->hasMany(PortfolioMedia::class);
    }
    public function bookmarks()
    {
        return $this->hasMany(BookmarkedPortfolio::class);
    }
}
