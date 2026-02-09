<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use App\Traits\ApiResponse;
use App\Http\Requests\EventStoreRequest;

class EventController extends Controller
{
    use ApiResponse;

    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function store(EventStoreRequest $request)
    {
        $validated = $request->validated();
        
        try {
            $event = $this->eventService->createEvent($validated);
            return $this->success($event, 'Event created successfully!', 200);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
}
