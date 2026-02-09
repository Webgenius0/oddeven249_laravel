<?php

namespace App\Traits;

use App\Models\Interaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasInteractions
{
    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'target');
    }

    // counts gulo eager load kora thakle attribute hishebe paben (views_count)
}
