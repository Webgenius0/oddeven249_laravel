<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function getAvailableUsersByRole($role)
    {
        // যেহেতু একজন ম্যানেজার এখন মাল্টিপল ইনফ্লুয়েন্সারের সাথে কাজ করতে পারে,
        // তাই whereNull('parent_id') আর প্রয়োজন নেই।
        return User::where('role', $role)
                    ->select('id', 'name', 'email', 'role', 'phone', 'avatar', 'country')
                    ->get();
    }

    public function findById($id)
    {
        return User::findOrFail($id);
    }

    public function assignToUser($influencerId, $managerId, array $permissions, $isExclusive = false)
    {
        $influencer = User::findOrFail($influencerId);

        $influencer->agencies()->syncWithoutDetaching([
            $managerId => [
                'permissions'  => json_encode($permissions),
                'is_exclusive' => $isExclusive
            ]
        ]);
        $agency = User::with(['clients' => function ($query) use ($influencerId) {
            $query->where('user_id', $influencerId); 
        }])->find($managerId);
        $agency->is_exclusive = (bool)$isExclusive;

        return $agency;
    }
}
