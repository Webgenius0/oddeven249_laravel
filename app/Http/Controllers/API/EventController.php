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

    // Dependency injection of the EventService
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    // Fetch all events with related sponsorships and collaborators
    public function index()
    {
        try {
            $events = $this->eventService->getAllEvents();

            if ($events->isEmpty()) {
                return $this->success([], 'No events found.', 200);
            }

            return $this->success($events, 'Events fetched successfully!', 200);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    // Create a new event with optional sponsors and collaborators
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
