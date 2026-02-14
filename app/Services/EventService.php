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

    // Fetch all events with related sponsorships and collaborators
    public function getAllEvents()
    {
        return $this->eventRepo->getAllEvents();
    }

    // Create a new event with optional sponsors and collaborators
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
            // 2.save sponsor(if exit)
            if (isset($data['sponsors']) && is_array($data['sponsors'])) {
                foreach ($data['sponsors'] as $sponsor) {
                    $event->event_sponsorships()->create([
                        'sponsor_id'     => $sponsor['user_id'],
                        'amount'         => $sponsor['amount'],
                        'payment_status' => 'pending',
                    ]);
                }
            }

            // 3.add collaborators(if exits)
            if (isset($data['collaborators']) && is_array($data['collaborators'])) {
                foreach ($data['collaborators'] as $userId) {
                    $event->event_collaborators()->attach($userId, [
                        'status' => 'invited',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return $event->load(['event_sponsorships', 'event_collaborators']);
        } catch (Exception $e) {

            DB::rollBack();
            throw new Exception("Error creating event: " . $e->getMessage());
        }
    }
}
