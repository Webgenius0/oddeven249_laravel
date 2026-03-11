<?php

namespace App\Services;

use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class EventService
{
    protected $eventRepo;
    protected $walletService;
    public function __construct(EventRepository $eventRepo, WalletService $walletService)
    {
        $this->eventRepo = $eventRepo;
        $this->walletService  = $walletService;

    }
    public function getAllEvents()
    {
        return $this->eventRepo->getAllEvents();
    }

    public function createEvent(array $data)
    {
        DB::beginTransaction();

        try {
            if (isset($data['photo'])) {
                $data['photo'] = uploadImage($data['photo'], 'event/photos');
            }

            $data['creator_id'] = auth()->id() ?? 1;
            $dbData = collect($data)->only([
                'creator_id',
                'title',
                'type',
                'entry_fee',
                'location',
                'full_location',
                'date',
                'description',
                'photo',
                'event_restriction',
                'is_published',
                'message',
            ])->toArray();

            $event = $this->eventRepo->store($dbData);
            if (isset($data['sponsors']) && is_array($data['sponsors'])) {
                foreach ($data['sponsors'] as $sponsor) {
                    $event->event_sponsorships()->create([
                        'sponsor_id'     => $sponsor['user_id'],
                        'amount'         => $sponsor['amount'],
                        'payment_status' => 'pending',
                    ]);
                }
            }
            if (isset($data['collaborators']) && is_array($data['collaborators'])) {
                foreach ($data['collaborators'] as $userId) {
                    $event->event_collaborators()->attach($userId, [
                        'status' => 'invited',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            if (isset($data['tickets']) && is_array($data['tickets'])) {
                foreach ($data['tickets'] as $ticket) {
                    $event->tickets()->create([
                        'ticket_type' => $ticket['type'],
                        'price'       => $ticket['price'] ?? 0,
                        'capacity'    => $ticket['capacity'] ?? null,
                    ]);
                }
            }
            DB::commit();
            return $event->load(['event_sponsorships', 'event_collaborators','tickets']);
        } catch (Exception $e) {

            DB::rollBack();
            throw new Exception("Error creating event: " . $e->getMessage());
        }
    }
    public function getEventDetails($id)
    {
        $event = $this->eventRepo->findEventById($id);

        $event->event_participants->transform(function ($user) {
            return [
                'id'        => $user->id,
                'name'      => $user->name,
                'joined_at' => $user->pivot->created_at->diffForHumans(),
                'role'      => 'Participant',
                'tickets_available' => $user->pivot->quantity - $user->pivot->used_quantity,
            ];
        });

        return [
            'name'               => $event->title,
            'location'           => $event->location,
            'full_location'      => $event->full_location,
            'sponsors'           => $event->event_sponsorships,
            'start_date'         => $event->date->format('Y-m-d H:i A'),
            'total_participants' => $event->event_participants_count,
            'time_left'          => $event->time_left,
            'tickets'            => $event->tickets,
            'participants'       => $event->event_participants,
        ];
    }
    public function registerForEvent(array $data)
    {
        $user   = auth()->user();
        $userId = $user->id;

        $event = DB::table('events')->find($data['event_id']);
        if (!$event) {
            throw new Exception("Event not found.", 404);
        }

        if ($event->creator_id == $userId) {
            throw new Exception("As an organizer, you cannot purchase tickets for your own event.", 403);
        }

        $ticket = DB::table('event_tickets')
            ->where('id', $data['ticket_id'])
            ->where('event_id', $data['event_id'])
            ->first();

        if (!$ticket) {
            throw new Exception("Ticket not found for this event.", 404);
        }

        $quantity = (int) ($data['quantity'] ?? 1);
        if ($quantity < 1) {
            throw new Exception("Quantity must be at least 1.", 422);
        }

        if ($ticket->capacity) {
            $sold = DB::table('event_participants')
                ->where('event_ticket_id', $ticket->id)
                ->sum('quantity');

            if (($sold + $quantity) > $ticket->capacity) {
                throw new Exception("Not enough tickets available. Remaining: " . ($ticket->capacity - $sold), 422);
            }
        }
        $existing = DB::table('event_participants')
            ->where('event_id', $data['event_id'])
            ->where('participant_id', $userId)
            ->where('event_ticket_id', $ticket->id)
            ->first();

        return DB::transaction(function () use ($user, $data, $ticket, $quantity, $existing, $event) {
            $totalPrice    = $ticket->price * $quantity;
            $paymentStatus = $totalPrice > 0 ? 'paid' : 'free';

            if ($totalPrice > 0) {
                $wallet = $this->walletService->getOrCreateWallet($user);

                if ($wallet->available_balance < $totalPrice) {
                    throw new Exception("Insufficient balance. Required: {$totalPrice}, Available: {$wallet->available_balance}", 422);
                }

                $this->walletService->withdraw(
                    user: $user,
                    amount: $totalPrice,
                    description: "Ticket purchase ({$quantity}x) — Event #{$data['event_id']}"
                );

                $organizer = \App\Models\User::find($event->creator_id);
                if ($organizer) {
                    $this->walletService->deposit(
                        user: $organizer,
                        amount: $totalPrice,
                        type: 'event_ticket_payment',
                        sourceType: 'event',
                        sourceId: $data['event_id'],
                        description: "{$quantity}x Ticket from {$user->name} — Event #{$data['event_id']}"
                    );
                }
            }

            // --- Unique 10-Digit Code Generation ---
            $ticketCode = null;
            if (!$existing) {
                do {
                    $ticketCode = strtoupper(\Illuminate\Support\Str::random(10));
                } while (DB::table('event_participants')->where('ticket_code', $ticketCode)->exists());
            }

            if ($existing) {
                DB::table('event_participants')
                    ->where('id', $existing->id)
                    ->increment('quantity', $quantity);

                $finalCode = $existing->ticket_code;
            } else {
                DB::table('event_participants')->insert([
                    'event_id'        => $data['event_id'],
                    'participant_id'  => $user->id,
                    'event_ticket_id' => $ticket->id,
                    'ticket_code'     => $ticketCode,
                    'quantity'        => $quantity,
                    'used_quantity'   => 0,
                    'payment_status'  => $paymentStatus,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                $finalCode = $ticketCode;
            }

            return [
                'event_id'       => $data['event_id'],
                'ticket_code'    => $finalCode,
                'ticket_type'    => $ticket->ticket_type,
                'quantity'       => $quantity,
                'amount_charged' => $totalPrice,
                'payment_status' => $paymentStatus,
            ];
        });
    }
    public function getEventParticipants($eventId, $userRole = null)
    {
        $event = DB::table('events')->find($eventId);

        if (!$event) {
            throw new Exception("Event not found.", 404);
        }
        if ($event->creator_id != auth()->id()) {
            throw new Exception("Unauthorized! Only the organizer can view this list.", 403);
        }

        $participants = $this->eventRepo->getParticipantsByEventId($eventId, $userRole);

        return $participants->map(function ($p) {
            return [
            'user_id'            => $p->user_id,
            'name'               => $p->name,
            'email'              => $p->email,
            'system_role'        => $p->user_role,
            'ticket_category'    => $p->ticket_category,
            'payment_status'     => $p->payment_status,
            'quantity'           => $p->quantity,
            'available_tickets'  => $p->quantity - $p->used_quantity,
            'used_quantity'      => $p->used_quantity,
            'is_invited'         => (bool) $p->is_invited,
            'joined_at'          => \Carbon\Carbon::parse($p->joined_at)->diffForHumans(),
    ];
        });
    }

    public function sendInvitation(array $data)
    {
        $inviterId = auth()->id();
        $event     = $this->eventRepo->findEventById($data['event_id']);
        if ($event->creator_id === $inviterId) {
            throw new Exception("Organizer cannot send ticket-transfer invitations.", 403);
        }
        $inviterTicket = DB::table('event_participants')
            ->where('event_id', $data['event_id'])
            ->where('participant_id', $inviterId)
            ->where('event_ticket_id', $data['ticket_id'])
            ->first();

        if (!$inviterTicket) {
            throw new Exception("You don't own this ticket type.", 403);
        }

        $availableTickets = $inviterTicket->quantity - $inviterTicket->used_quantity;
        if ($availableTickets < 1) {
            throw new Exception("You have no available tickets to transfer.", 422);
        }
        $ticket = DB::table('event_tickets')->find($data['ticket_id']);
        if (!$ticket || $ticket->event_id != $data['event_id']) {
            throw new Exception("Invalid ticket for this event.", 422);
        }
        $exists = DB::table('event_invitations')
            ->where('event_id', $data['event_id'])
            ->where('invited_user_id', $data['invited_user_id'])
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($exists) {
            throw new Exception("This user has already been invited.", 409);
        }
        $registered = DB::table('event_participants')
            ->where('event_id', $data['event_id'])
            ->where('participant_id', $data['invited_user_id'])
            ->exists();

        if ($registered) {
            throw new Exception("This user is already registered.", 409);
        }

        return DB::transaction(function () use ($inviterId, $inviterTicket, $data) {
            DB::table('event_participants')
                ->where('id', $inviterTicket->id)
                ->increment('used_quantity', 1);

            return \App\Models\EventInvitation::create([
                'event_id'        => $data['event_id'],
                'invited_by'      => $inviterId,
                'invited_user_id' => $data['invited_user_id'],
                'event_ticket_id' => $data['ticket_id'],
                'message'         => $data['message'] ?? null,
                'status'          => 'pending',
                'payment_status'  => 'none',
            ]);
        });
    }

    public function handleInvitationAction(array $data)
    {
        $user       = auth()->user();
        $invitation = \App\Models\EventInvitation::with('ticket')->findOrFail($data['invitation_id']);

        if ($invitation->invited_user_id !== $user->id) {
            throw new Exception("Unauthorized.", 403);
        }

        if ($invitation->status !== 'pending') {
            throw new Exception("This invitation has already been responded to.", 400);
        }

        if ($data['status'] === 'rejected') {
            DB::table('event_participants')
                ->where('event_id', $invitation->event_id)
                ->where('participant_id', $invitation->invited_by)
                ->where('event_ticket_id', $invitation->event_ticket_id)
                ->decrement('used_quantity', 1);

            $invitation->update(['status' => 'rejected']);
            return $invitation;
        }

        $ticket = $invitation->ticket;
        return DB::transaction(function () use ($user, $invitation) {
            DB::table('event_participants')->insert([
                'event_id'        => $invitation->event_id,
                'participant_id'  => $user->id,
                'event_ticket_id' => $invitation->event_ticket_id,
                'quantity'        => 1,
                'used_quantity'   => 0,
                'payment_status'  => 'free',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $invitation->update(['status' => 'accepted']);
            return $invitation->fresh();
        });
    }

    public function getMyInvitations($status = null)
    {

        return \App\Models\EventInvitation::with([
            'event:id,title,date,location,photo',
            'invitedBy:id,name',
            'ticket:id,ticket_type,price',
        ])
            ->where('invited_user_id', auth()->id())
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->get();
    }
    public function requestPaymentForInvitation(array $data)
    {
        $user       = auth()->user();
        $invitation = \App\Models\EventInvitation::findOrFail($data['invitation_id']);

        if ($invitation->invited_user_id != $user->id) {
            throw new Exception("Unauthorized.", 403);
        }

        if ($invitation->status !== 'pending') {
            throw new Exception("This invitation is no longer pending.", 400);
        }

        if ($invitation->payment_status !== 'none') {
            throw new Exception("Payment request already sent.", 409);
        }

        $ticket = DB::table('event_tickets')->find($invitation->event_ticket_id);

        $requestedAmount = isset($data['amount']) && $data['amount'] > 0
            ? (float) $data['amount']
            : ($ticket ? (float) $ticket->price : 0);

        if ($requestedAmount <= 0) {
            throw new Exception("Invalid amount requested.", 422);
        }

        $invitation->update([
            'is_payment_requested' => true,
            'payment_status'       => 'requested',
            'requested_amount'     => $requestedAmount,
        ]);

        return [
            'invitation_id'    => $invitation->id,
            'ticket_price'     => $ticket->price ?? 0,
            'requested_amount' => $requestedAmount,
            'message'          => 'Payment request sent to inviter.',
        ];
    }
    public function approvePaymentRequest(array $data)
    {
        $inviter    = auth()->user();
        $invitation = \App\Models\EventInvitation::with('ticket')->findOrFail($data['invitation_id']);

        if ((int) $invitation->invited_by !== (int) $inviter->id) {

            throw new Exception("Unauthorized.", 403);
        }

        if ($invitation->payment_status !== 'requested') {
            throw new Exception("No pending payment request found.", 400);
        }

        if ($data['action'] === 'rejected') {
            $invitation->update(['payment_status' => 'rejected']);
            return ['message' => 'Payment request rejected.'];
        }
        return DB::transaction(function () use ($inviter, $invitation) {
            $ticket      = $invitation->ticket;
            $invitedUser = \App\Models\User::find($invitation->invited_user_id);

            $amount = (float) $invitation->requested_amount ?: (float) $ticket->price;

            $wallet = $this->walletService->getOrCreateWallet($inviter);
            if ($wallet->available_balance < $amount) {
                throw new Exception(
                    "Insufficient balance. Required: {$amount}, Available: {$wallet->available_balance}",
                    422
                );
            }
            $this->walletService->withdraw(
                user:        $inviter,
                amount:      $amount,
                description: "Payment to {$invitedUser->name} — Event #{$invitation->event_id}"
            );
            $this->walletService->deposit(
                user:        $invitedUser,
                amount:      $amount,
                type:        'deposit',
                sourceType:  'event_invitation',
                sourceId:    $invitation->id,
                description: "Payment received from {$inviter->name} — Event #{$invitation->event_id}"
            );

            $invitation->update([
                'payment_status' => 'approved',
            ]);

            return ['message' => 'Payment approved successfully.'];
        });
    }
    public function getMySentInvitations($filters = [])
    {
        return \App\Models\EventInvitation::with([
            'event:id,title,date,location',
            'invitedUser:id,name,email',
            'ticket:id,ticket_type,price',
        ])
            ->where('invited_by', auth()->id())
            ->when($filters['event_id'] ?? null, fn ($q, $v) => $q->where('event_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->get();
    }
}
