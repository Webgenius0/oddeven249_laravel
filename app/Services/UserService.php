<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Exception;

class UserService
{
    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function assignExistingManager(array $data)
    {
        try {
            $manager = $this->userRepo->findById($data['user_id']);

            if (!$manager || !in_array($manager->role, ['business_manager'])) {
                throw new Exception("The selected user is not a valid Business Manager or Agency!");
            }

            $inputPermissions = $data['permissions'] ?? [];
            $permissions = [
                'view_deal'           => filter_var($inputPermissions['view_deal'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'accept_reject_deals' => filter_var($inputPermissions['accept_reject_deals'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'chat_with_client'    => filter_var($inputPermissions['chat_with_client'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'view_portfolio'      => filter_var($inputPermissions['view_portfolio'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'manage_contests'     => filter_var($inputPermissions['manage_contests'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'view_earning'        => filter_var($inputPermissions['view_earning'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            // Many-to-Many logic: current user (influencer) assigning a manager
            return $this->userRepo->assignToUser(auth()->id(), $manager->id, $permissions);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function assignAgency(array $data)
    {
        try {
            $agency = $this->userRepo->findById($data['user_id']);

            if (!$agency || $agency->role !== 'agency') {
                throw new Exception("The selected user is not a valid Agency!");
            }

            $permissions = [
                'chat_permission'         => filter_var($data['permissions']['chat'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'portfolio_permission'    => filter_var($data['permissions']['portfolio'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'voucher_add_permission'  => filter_var($data['permissions']['voucher'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'deal_manage_permission'  => filter_var($data['permissions']['deal'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'create_event_permission' => filter_var($data['permissions']['event'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            return $this->userRepo->assignToUser(auth()->id(), $agency->id, $permissions);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getAvailableListByRole($role)
    {
        return $this->userRepo->getAvailableUsersByRole($role);
    }
    // UserService.php

    public function getMyAssignedTeamByRole($user, $role)
    {
        return $user->agencies()
            ->where('role', $role)
            ->select('users.id', 'users.name', 'users.email', 'users.avatar')
            ->get()
            ->map(function ($member) {
                // পিভট টেবিল থেকে পারমিশন ডিকোড করা
                $member->permissions = $member->pivot->permissions
                    ? json_decode($member->pivot->permissions, true)
                    : null;

                // ক্লিন আউটপুটের জন্য পিভট অবজেক্টটি সরিয়ে ফেলা
                unset($member->pivot);
                return $member;
            });
    }
}
