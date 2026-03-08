<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use App\Traits\ApiResponse;
use App\Http\Requests\EventStoreRequest;
use Symfony\Component\HttpFoundation\Request;

class EventController extends Controller
{
    use ApiResponse;

    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

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
    public function show(Request $request)
    {
        $id = $request->id;
        try {
            $eventDetails = $this->eventService->getEventDetails($id);
            return $this->success($eventDetails, 'Event details fetched successfully!', 200);
        } catch (\Exception $e) {
            return $this->error(null, "Event not found or " . $e->getMessage(), 404);
        }
    }
    public function registerTicket(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'ticket_id' => 'required|exists:event_tickets,id',
        ]);

        try {
            $this->eventService->registerForEvent($request->all());
            return $this->success(null, 'Registration successful! Please wait for approval.', 200);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 400);
        }
    }
    public function getParticipants(Request $request)
    {
        $id = $request->input('event_id');
        $role = $request->query('role');
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['event_id' => $id, 'role' => $role],
            [
                'event_id' => 'required|integer|exists:events,id',
                'role'     => 'nullable|string'
            ]
        );

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $participants = $this->eventService->getEventParticipants($id, $role);

            return $this->success($participants, 'Participants fetched successfully!');
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 400;
            return $this->error(null, $e->getMessage(), $statusCode);
        }
    }
    // Invite send
    public function sendInvitation(Request $request)
    {
        $request->validate([
            'event_id'        => 'required|exists:events,id',
            'invited_user_id' => 'required|exists:users,id',
            'ticket_id'       => 'required|exists:event_tickets,id',
            'message'         => 'nullable|string|max:500',
        ]);

        try {
            $invitation = $this->eventService->sendInvitation($request->all());
            return $this->success($invitation, 'Invitation sent successfully!', 201);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    // Accept / Reject
    public function handleInvitation(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:event_invitations,id',
            'status'        => 'required|in:accepted,rejected',
        ]);

        try {
            $invitation = $this->eventService->handleInvitationAction($request->all());
            return $this->success($invitation, 'Invitation ' . $request->status . ' successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function myInvitations(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,accepted,rejected',
        ]);

        try {
            $invitations = $this->eventService->getMyInvitations($request->status);
            return $this->success($invitations, 'Invitations retrieved successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function requestPayment(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:event_invitations,id',
            'amount'        => 'nullable|numeric|min:1',
        ]);

        try {
            $result = $this->eventService->requestPaymentForInvitation($request->all());
            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function approvePayment(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:event_invitations,id',
            'action'        => 'required|in:approved,rejected',
        ]);

        try {
            $result = $this->eventService->approvePaymentRequest($request->all());
            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), $e->getCode() ?: 500);
        }
    }
    public function mySentInvitations(Request $request)
    {
        $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'status'   => 'nullable|in:pending,accepted,rejected',
        ]);

        try {
            $invitations = $this->eventService->getMySentInvitations($request->all());
            return $this->success($invitations, 'Sent invitations retrieved successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
}
