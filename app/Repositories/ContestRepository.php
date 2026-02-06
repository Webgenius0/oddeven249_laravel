<?php

namespace App\Repositories;

use App\Models\Contest;
use Illuminate\Support\Facades\DB;

class ContestRepository
{
    public function store(array $data)
    {

        return Contest::create($data);
    }

    public function findById($id)
    {
        return Contest::withCount('participants')->findOrFail($id);
    }
    public function getAllContests()
    {
        return Contest::with(['creator:id,name,email'])
            ->withCount('participants')
            ->latest()
            ->get();
    }
    public function getContestsByUserId($userId)
    {
        return Contest::where('creator_id', $userId)
            ->withCount('participants')
            ->latest()
            ->get();
    }
    public function update($id, array $data)
    {
        $contest = Contest::findOrFail($id);
        $contest->update($data);
        return $contest;
    }
    public function getParticipatedContests($userId)
    {
        return Contest::whereHas('participants', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->with(['creator:id,name'])
        ->withCount('participants')
        ->latest()
        ->get();
    }
    public function isUserJoined($contestId, $userId)
    {
        return DB::table('contest_participants')
            ->where('contest_id', $contestId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function joinContest($contestId, $userId)
    {
        return DB::table('contest_participants')->insert([
            'contest_id' => $contestId,
            'user_id'    => $userId,
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function getContestForDetails($id)
    {
        return Contest::withCount('participants')->findOrFail($id);
    }

    public function getParticipantsByContest($contestId, $role = null)
    {
        $contest = Contest::findOrFail($contestId);

        $query = $contest->participants();
        if ($role) {
            $query->where('role', $role);
        }

        return $query->withPivot('payment_status', 'created_at')->get();
    }
}
