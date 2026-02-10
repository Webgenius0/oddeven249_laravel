<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;

    // Roles definition
    public const ROLE_INFLUENCER = 'influencer';
    public const ROLE_ADVERTISER = 'advertiser';
    public const ROLE_AGENCY = 'agency';
    public const ROLE_BUSINESS_MANAGER = 'business_manager';
    public const ROLE_GUEST = 'guest';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'phone_code',
        'country',
        'website_link',
        'category_id',
        'is_exclusive'
    ];

    protected $casts = [
        'is_exclusive' => 'boolean'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];


    public function agencies()
    {
        return $this->belongsToMany(User::class, 'business_manager_assignments', 'user_id', 'manager_id')
                    ->withPivot('permissions')
                    ->withTimestamps();
    }
    public function clients()
    {
        return $this->belongsToMany(User::class, 'business_manager_assignments', 'manager_id', 'user_id')
                    ->withPivot('permissions')
                    ->withTimestamps();
    }

    public function isBusinessManager()
    {
        return $this->role === self::ROLE_BUSINESS_MANAGER;
    }

    public function isAgency()
    {
        return $this->role === self::ROLE_AGENCY;
    }

    public function isInfluencer()
    {
        return $this->role === self::ROLE_INFLUENCER;
    }
    
    public function dealsAsSeller()
    {
        return $this->hasMany(Deal::class, 'seller_id');
    }

    public function dealsAsBuyer()
    {
        return $this->hasMany(Deal::class, 'buyer_id');
    }

    public function requestedDeals()
    {
        return $this->hasMany(Deal::class, 'requested_by');
    }

    public function createdContests()
    {
        return $this->hasMany(Contest::class, 'creator_id');
    }

    public function sponsoredContests()
    {
        return $this->hasMany(Sponsorship::class, 'sponsor_id');
    }

    public function collaborationContests()
    {
        return $this->belongsToMany(Contest::class, 'contest_collaborators')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function participatedContests()
    {
        return $this->hasMany(ContestParticipant::class);
    }
}
