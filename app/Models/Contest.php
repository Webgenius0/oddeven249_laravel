<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contest extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function sponsorships()
    {
        return $this->hasMany(Sponsorship::class);
    }

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'contest_collaborators')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function participants()
    {
        return $this->hasMany(ContestParticipant::class);
    }
}
