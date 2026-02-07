<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function getAvailableUsersByRole($role)
    {
        return User::where('role', $role)
                   ->whereNull('parent_id')
                   ->select('id', 'name', 'email', 'phone', 'avatar', 'country')
                   ->get();
    }

    public function findById($id)
    {
        return User::findOrFail($id);
    }

    public function updateManager($id, array $data)
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

}
