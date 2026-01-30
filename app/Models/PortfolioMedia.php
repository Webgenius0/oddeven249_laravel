<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioMedia extends Model
{
    protected $guarded = [];

    protected $hidden = ['created_at','updated_at'];

    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }
}
