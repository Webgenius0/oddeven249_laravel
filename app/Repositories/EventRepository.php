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
    public function findEventById($id)
    {
        return Event::with([
                'tickets',
                'event_sponsorships.sponsor:id,name,email',
                'event_participants' => function ($query) {
                    $query->select('users.id', 'users.name')
                          ->wherePivot('quantity', '>', DB::raw('event_participants.used_quantity')) // ✅
                          ->orderBy('event_participants.created_at', 'desc')
                          ->withPivot('created_at', 'payment_status', 'quantity', 'used_quantity');
                }
            ])
            ->withCount([
                'event_participants as event_participants_count' => function ($query) {
                    $query->whereRaw('event_participants.quantity > event_participants.used_quantity'); // ✅ count ও filter
                }
        ])
            ->findOrFail($id);
    }
    // App\Repositories\EventRepository.php

    public function getParticipantsByEventId($eventId, $userRole = null)
    {
        $query = DB::table('event_participants')
            ->join('users', 'event_participants.participant_id', '=', 'users.id')
            ->join('event_tickets', 'event_participants.event_ticket_id', '=', 'event_tickets.id')
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.role as user_role',
                'event_tickets.ticket_type as ticket_category',
                'event_participants.payment_status',
                'event_participants.quantity',
                'event_participants.used_quantity',
                'event_participants.created_at as joined_at',
                DB::raw('CASE WHEN event_participants.payment_status = "free" THEN 1 ELSE 0 END as is_invited') // ✅
            )
            ->where('event_participants.event_id', $eventId)
            ->whereRaw('event_participants.quantity > event_participants.used_quantity');

        if ($userRole) {
            $query->where('users.role', $userRole);
        }

        return $query->orderBy('event_participants.created_at', 'desc')->get();
    }
}
