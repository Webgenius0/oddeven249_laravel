<?php

namespace App\Services;

use App\Repositories\ContestRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class ContestService
{
    protected $contestRepo;

    public function __construct(ContestRepository $contestRepo)
    {
        $this->contestRepo = $contestRepo;
    }

    public function createContest(array $data)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $targetCreatorId = $user->id;
            $createdBy = null;
            if (!empty($data['creator_id'])) {
                $requestedUserId = (int) $data['creator_id'];

                if ($user->id !== $requestedUserId && ($user->isAgency() || $user->isBusinessManager())) {
                    if (!$user->clients()->where('user_id', $requestedUserId)->exists()) {
                        throw new Exception("Unauthorized: You are not the manager of this user.");
                    }

                    $targetCreatorId = $requestedUserId;
                    $createdBy = $user->id;
                }
            }

            if (isset($data['prize_photo'])) {
                $data['prize_photo_url'] = uploadImage($data['prize_photo'], 'contests/photos');
            }
            if (isset($data['document'])) {
                $data['document_url'] = uploadImage($data['document'], 'contests/docs');
            }
            $dbData = collect($data)->only([
                'title',
                'description',
                'rules',
                'prize',
                'end_date',
                'entry_fee',
                'total_slots',
                'is_published'
            ])->toArray();

            $dbData['creator_id'] = $targetCreatorId;
            $dbData['created_by'] = $createdBy;
            $dbData['prize_photo_url'] = $data['prize_photo_url'] ?? null;
            $dbData['document_url'] = $data['document_url'] ?? null;

            $contest = $this->contestRepo->store($dbData);
            if (isset($data['sponsors']) && is_array($data['sponsors'])) {
                foreach ($data['sponsors'] as $sponsor) {
                    $contest->sponsorships()->create([
                        'sponsor_id'     => $sponsor['user_id'],
                        'amount'         => $sponsor['amount'],
                        'payment_status' => $sponsor['payment_status'] ?? 'pending',
                    ]);
                }
            }

            if (isset($data['collaborators']) && is_array($data['collaborators'])) {
                $contest->collaborators()->attach($data['collaborators'], [
                    'status' => 'invited',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return $contest->load(['sponsorships', 'collaborators', 'createdBy']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error creating contest: " . $e->getMessage());
        }
    }
    public function getContestsForIndex()
    {
        return $this->contestRepo->getAllContests();
    }
    public function getMyContests()
    {
        $userId = auth()->id();
        return $this->contestRepo->getContestsByUserId($userId);
    }
    public function getContestDetails($id)
    {
        return $this->contestRepo->findById($id)->load(['creator', 'sponsorships.sponsor', 'collaborators', 'participants']);
    }

    public function updateContest($id, array $data)
    {
        DB::beginTransaction();
        try {
            $contest = $this->contestRepo->findById($id);

            if (isset($data['prize_photo'])) {
                $data['prize_photo_url'] = uploadImage($data['prize_photo'], 'contests/photos');
            }
            if (isset($data['document'])) {
                $data['document_url'] = uploadImage($data['document'], 'contests/docs');
            }

            $dbData = collect($data)->only([
                'title',
                'description',
                'rules',
                'prize',
                'end_date',
                'entry_fee',
                'total_slots',
                'prize_photo_url',
                'document_url',
                'is_published'
            ])->toArray();

            $contest = $this->contestRepo->update($id, $dbData);

            if (!empty($data['deleted_sponsor_ids'])) {
                $contest->sponsorships()->whereIn('id', $data['deleted_sponsor_ids'])->delete();
            }

            if (isset($data['sponsors']) && is_array($data['sponsors'])) {
                foreach ($data['sponsors'] as $sponsor) {

                    $contest->sponsorships()->updateOrCreate(
                        ['id' => $sponsor['id'] ?? null],
                        [
                            'sponsor_id'     => $sponsor['user_id'],
                            'amount'         => $sponsor['amount'],
                            'payment_status' => $sponsor['payment_status'] ?? 'pending'
                        ]
                    );
                }
            }


            if (!empty($data['deleted_collaborator_ids'])) {
                $contest->collaborators()->detach($data['deleted_collaborator_ids']);
            }

            if (isset($data['collaborators']) && is_array($data['collaborators'])) {
                $collaboratorData = [];
                foreach ($data['collaborators'] as $userId) {
                    $collaboratorData[$userId] = ['status' => 'invited', 'updated_at' => now()];
                }
                $contest->collaborators()->syncWithoutDetaching($collaboratorData);
            }

            DB::commit();
            return $contest->load(['sponsorships', 'collaborators']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error updating contest: " . $e->getMessage());
        }
    }
    public function getUserParticipatedContests()
    {
        $userId = auth()->id();
        return $this->contestRepo->getParticipatedContests($userId);
    }
    public function participateInContest($contestId)
    {
        $userId = auth()->id();
        $contest = $this->contestRepo->findById($contestId);

        if (!$contest) {
            throw new Exception("Contest not found!");
        }
        //    1. contest ended or not?
        if (now() > $contest->end_date) {
            throw new Exception("Registration for this contest has ended.");
        }
        // 2.user already joined or not
        if ($this->contestRepo->isUserJoined($contestId, $userId)) {
            throw new Exception("You have already participated in this contest.");
        }
        // 3. slot available or not
        $currentParticipants = DB::table('contest_participants')->where('contest_id', $contestId)->count();
        if ($currentParticipants >= $contest->total_slots) {
            throw new Exception("All slots for this contest are full.");
        }
        // ৪.join in contest
        return $this->contestRepo->joinContest($contestId, $userId);
    }
    public function getContestDetailsData($id)
    {
        $contest = $this->contestRepo->getContestForDetails($id)
            ->load(['creator', 'sponsorships.sponsor', 'collaborators', 'participants']);
        $now = now();
        $endDate = \Carbon\Carbon::parse($contest->end_date);
        $timeLeft = $now->diffInDays($endDate, false);

        $totalSlots = $contest->total_slots;
        $currentParticipants = $contest->participants_count;
        $progressPercent = ($totalSlots > 0) ? round(($currentParticipants / $totalSlots) * 100, 2) : 0;

        return [
            'id'                    => $contest->id,
            'title'                 => $contest->title,
            'prize'                 => $contest->prize,
            'prize_image'           => $contest->prize_photo_url,
            'total_slots'           => $totalSlots,
            'total_participants'    => $currentParticipants,
            'participation_progress' => $progressPercent . '%',
            'time_left_days'        => $timeLeft > 0 ? $timeLeft : 0,
            'start_time'            => $contest->created_at->format('Y-m-d H:i:s'),
            'end_time'              => $contest->end_date,
            'creator'               => $contest->creator,
            'all_participants'      => $contest->participants,
            'sponsors'              => $contest->sponsorships,
            'collaborators'         => $contest->collaborators,
        ];
    }
    public function getContestParticipantsData($contestId, $role = null)
    {

        $participants = $this->contestRepo->getParticipantsByContest($contestId, $role);
        return $participants->map(function ($user) {
            return [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'photo'          => $user->photo_url,
                'payment_status' => $user->pivot->payment_status ?? 'N/A',
                'joined_at'      => $user->pivot->created_at ? $user->pivot->created_at->format('Y-m-d H:i:s') : 'N/A',
            ];
        });
    }
    public function announceWinner($contestId, $winnerId)
    {
        $contest = $this->contestRepo->findById($contestId);
        if ($contest->creator_id !== auth()->id()) {
            throw new Exception("You are not authorized to announce a winner for this contest.");
        }

        $isParticipant = $this->contestRepo->isUserJoined($contestId, $winnerId);
        if (!$isParticipant) {
            throw new Exception("The selected user is not a participant of this contest.");
        }

        if ($contest->status === 'completed') {
            throw new Exception("A winner has already been announced for this contest.");
        }
        return $this->contestRepo->setWinner($contestId, $winnerId);
    }
}
