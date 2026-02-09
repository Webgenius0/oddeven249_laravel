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

    public function assignToUser($influencerId, $managerId, array $permissions)
    {
        $influencer = User::findOrFail($influencerId);

        // syncWithoutDetaching ব্যবহার করলে আগের এসাইনমেন্ট ডিলিট হবে না,
        // শুধু নতুনটা অ্যাড হবে বা আপডেট হবে।
        $influencer->agencies()->syncWithoutDetaching([
            $managerId => ['permissions' => json_encode($permissions)]
        ]);

        return User::find($managerId);
    }
}
