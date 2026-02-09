<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class EventRepository
{

    public function store(array $data)
    {
        return Event::create($data);
    }
}
