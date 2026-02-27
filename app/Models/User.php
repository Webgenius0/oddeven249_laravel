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
    public const ROLE_INFLUENCER       = 'influencer';
    public const ROLE_ADVERTISER       = 'advertiser';
    public const ROLE_AGENCY           = 'agency';
    public const ROLE_BUSINESS_MANAGER = 'business_manager';
    public const ROLE_GUEST            = 'guest';

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
        'is_exclusive',
    ];

    protected $casts = [
        'is_exclusive' => 'boolean',
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

    public function scopeOnline($query)
    {return $query->whereNotNull('last_seen_at')->where('last_seen_at', '>', now()->subMinutes(2));}

    // Chat relations
    // I blocked them
    public function blockedUsers()
    {return $this->belongsToMany(User::class, 'user_blocks', 'user_id', 'blocked_id')->withTimestamps();}

    // They blocked me
    public function blockedByUsers()
    {return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'user_id')->withTimestamps();}

    // I restricted them
    public function restrictedUsers()
    {return $this->belongsToMany(User::class, 'user_restricts', 'user_id', 'restricted_id')->withTimestamps();}

    // They restricted me
    public function restrictedByUsers()
    {return $this->belongsToMany(User::class, 'user_restricts', 'restricted_id', 'user_id')->withTimestamps();}

    // Blocks check
    public function hasBlocked(User $user): bool
    {return $this->blockedUsers()->where('users.id', $user->id)->exists();}

    public function isBlockedBy(User $user): bool
    {return $this->blockedByUsers()->where('users.id', $user->id)->exists();}

    public function hasRestricted(User $user): bool
    {return $this->restrictedUsers()->where('users.id', $user->id)->exists();}

    // public function isRestrictedBy(User $user): bool
    // {
    //     return $this->restrictedByUsers()->where('users.id', $user->id)->exists();
    // }

    public function isOnline(): bool
    {return $this->last_seen_at && $this->last_seen_at->greaterThan(now()->subMinutes(2));}

    // public function deviceTokens()
    // {return $this->hasMany(DeviceToken::class);}

    //  Chat
}
