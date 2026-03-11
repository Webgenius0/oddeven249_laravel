<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use App\Traits\ApiResponse;
use App\Http\Requests\EventStoreRequest;
use Illuminate\Support\Facades\DB;
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
    public function verifyTicketCode(Request $request)
    {
        $request->validate([
            'ticket_code' => 'required|string|size:10',
        ]);
        $participant = DB::table('event_participants')
            ->where('ticket_code', $request->ticket_code)
            ->first();

        if (!$participant) {
            return $this->error(null, 'Invalid ticket code. No purchase found.', 200);
        }
        if ($participant->used_quantity >= $participant->quantity) {
            return $this->error(null, 'This ticket has already been used.', 200);
        }
        $user = DB::table('users')->find($participant->participant_id);
        $event = DB::table('events')->find($participant->event_id);
        if (!$user || !$event) {
            return $this->error(null, 'Associated user or event not found.', 200);
        }

        $responseData = [
            'event_name'     => $event->title,
            'participant'    => $user->name,
            'total_quantity' => $participant->quantity,
            'used_quantity'  => $participant->used_quantity,
            'remaining'      => $participant->quantity - $participant->used_quantity,
            'payment_status' => $participant->payment_status,
            'purchased_at'   => $participant->created_at
        ];

        return $this->success($responseData, 'Ticket verified successfully!', 200);
    }
    public function getUserTickets()
    {
        $user = auth()->user();
        
        $tickets = DB::table('event_participants')
            ->join('events', 'event_participants.event_id', '=', 'events.id')
            ->join('event_tickets', 'event_participants.event_ticket_id', '=', 'event_tickets.id')
            ->where('event_participants.participant_id', $user->id)
            ->select(
                'event_participants.id as registration_id',
                'event_participants.ticket_code',
                'event_participants.quantity',
                'event_participants.used_quantity',
                'event_participants.payment_status',
                'events.title as event_title',
                'events.date',
                'events.location',
                'event_tickets.ticket_type as ticket_name',
                'event_participants.created_at as purchased_at'
            )
            ->orderBy('event_participants.created_at', 'desc')
            ->get();

        if ($tickets->isEmpty()) {
            return $this->success([], 'You have not purchased any tickets yet.', 200);
        }

        return $this->success($tickets, 'Tickets retrieved successfully!', 200);
    }
    public function showTicketDetails(Request $request)
    {
        // validation check - jodi ticket_code na thake tahole automatic error dibe
        $request->validate([
            'ticket_code' => 'required|string|size:10',
        ]);

        $user = auth()->user();
        $ticketCode = $request->query('ticket_code');

        $ticket = DB::table('event_participants')
            ->join('events', 'event_participants.event_id', '=', 'events.id')
            ->join('event_tickets', 'event_participants.event_ticket_id', '=', 'event_tickets.id')
            ->where('event_participants.ticket_code', $ticketCode)
            ->where('event_participants.participant_id', $user->id)
            ->select(
                'event_participants.id as registration_id',
                'event_participants.ticket_code',
                'event_participants.quantity',
                'event_participants.used_quantity',
                'event_participants.payment_status',
                'events.title as event_title',
                'events.date',
                'events.location',
                'event_tickets.ticket_type',
                'event_participants.created_at as purchased_at'
            )
            ->first();

        if (!$ticket) {
            return $this->error(null, 'Ticket not found or unauthorized access.', 404);
        }

        return $this->success($ticket, 'Ticket details retrieved.', 200);
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
