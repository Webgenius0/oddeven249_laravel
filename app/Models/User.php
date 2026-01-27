<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;

    public const ROLE_INFLUENCER = 'influencer';
    public const ROLE_ADVISER = 'adviser';
    public const ROLE_AGENCY = 'agency';
    public const ROLE_BUSINESS_MANAGER = 'business_manager';
    public const ROLE_GUEST = 'guest';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_code',
        'country',
        'avatar',
        'role',
        'website_link',
        'category_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'provider',
        'provider_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isInfluencer()
    {
        return $this->role === self::ROLE_INFLUENCER;
    }

    public function isAdviser()
    {
        return $this->role === self::ROLE_ADVISER;
    }

    public function isAgency()
    {
        return $this->role === self::ROLE_AGENCY;
    }

    public function isBusinessManager()
    {
        return $this->role === self::ROLE_BUSINESS_MANAGER;
    }

    public function isGuest()
    {
        return $this->role === self::ROLE_GUEST;
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles)
    {
        return in_array($this->role, $roles);
    }

    // Get all available roles
    public static function getRoles()
    {
        return [
            self::ROLE_INFLUENCER,
            self::ROLE_ADVISER,
            self::ROLE_AGENCY,
            self::ROLE_BUSINESS_MANAGER,
            self::ROLE_GUEST,
        ];
    }

    // Scope for filtering by role
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeInfluencers($query)
    {
        return $query->where('role', self::ROLE_INFLUENCER);
    }

    public function scopeAdvisers($query)
    {
        return $query->where('role', self::ROLE_ADVISER);
    }

    public function scopeAgencies($query)
    {
        return $query->where('role', self::ROLE_AGENCY);
    }

    public function scopeBusinessManagers($query)
    {
        return $query->where('role', self::ROLE_BUSINESS_MANAGER);
    }

    public function scopeGuests($query)
    {
        return $query->where('role', self::ROLE_GUEST);
    }
}
