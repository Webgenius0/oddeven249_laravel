<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class EventRepository
{
    public function getAllEvents()
    {
        return Event::select('id', 'title', 'location', 'full_location', 'date', 'photo')
            ->with([
                'event_sponsorships',
                'event_sponsorships.sponsor:id,name,email'
            ])
            ->get();
    }
    public function store(array $data)
    {
        return Event::create($data);
    }
}
