<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'promo_code',
        'description',
        'discount',
        'discount_type',
        'start_date',
        'end_date',
        'status',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'boolean',
        'discount' => 'float',
    ];

    protected $hidden = ['created_at','updated_at'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
