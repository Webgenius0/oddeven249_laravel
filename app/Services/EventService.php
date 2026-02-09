<?php

namespace App\Services;

use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class EventService
{
    protected $eventRepo;

    public function __construct(EventRepository $eventRepo)
    {
        $this->eventRepo = $eventRepo;
    }

    public function createEvent(array $data) {}
}
