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
            if (isset($data['prize_photo'])) {
                $data['prize_photo_url'] = uploadImage($data['prize_photo'], 'contests/photos');
            }
            if (isset($data['document'])) {
                $data['document_url'] = uploadImage($data['document'], 'contests/docs');
            }

            $data['creator_id'] = auth()->id() ?? 1;
            $dbData = collect($data)->only([
                        'creator_id', 'title', 'description', 'rules', 'prize',
                        'end_date', 'entry_fee', 'total_slots', 'prize_photo_url', 'document_url', 'is_published'
                    ])->toArray();

            $contest = $this->contestRepo->store($dbData);
            // 2.save sponsor(if exit)
            if (isset($data['sponsors']) && is_array($data['sponsors'])) {
                foreach ($data['sponsors'] as $sponsor) {
                    $contest->sponsorships()->create([
                        'sponsor_id'     => $sponsor['user_id'],
                        'amount'         => $sponsor['amount'],
                        'payment_status' => $sponsor['payment_status'] ?? 'pending',
                    ]);
                }
            }

            // 3.add collaborators(if exits)
            if (isset($data['collaborators']) && is_array($data['collaborators'])) {
                foreach ($data['collaborators'] as $userId) {
                    $contest->collaborators()->attach($userId, [
                        'status' => 'invited',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return $contest->load(['sponsorships', 'collaborators']);

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
                'title', 'description', 'rules', 'prize',
                'end_date', 'entry_fee', 'total_slots', 'prize_photo_url', 'document_url', 'is_published'
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
}
