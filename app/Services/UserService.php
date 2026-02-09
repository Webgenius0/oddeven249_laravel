<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
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
            $user = $this->userRepo->findById($data['user_id']);
            if (!$user || $user->role !== 'business_manager') {
                throw new Exception("The selected user is not a valid Business Manager!");
            }
            if ($user->parent_id !== null && $user->parent_id != auth()->id()) {
                throw new Exception("This manager is already assigned to another user.");
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

            $updateData = [
                'parent_id'           => auth()->id(),
                'country'             => $data['country'] ?? $user->country,
                'manager_permissions' => $permissions,
            ];

            return $this->userRepo->updateManager($user->id, $updateData);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function assignAgency(array $data)
    {
        try {
            $user = $this->userRepo->findById($data['user_id']);

            if (!$user || $user->role !== 'agency') {
                throw new Exception("The selected user is not a valid Agency!");
            }
            if ($user->parent_id !== null && $user->parent_id != auth()->id()) {
                throw new Exception("This agency is already assigned to another user.");
            }

            $permissions = [
                'chat_permission'         => filter_var($data['permissions']['chat'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'portfolio_permission'    => filter_var($data['permissions']['portfolio'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'voucher_add_permission'  => filter_var($data['permissions']['voucher'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'deal_manage_permission'  => filter_var($data['permissions']['deal'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'create_event_permission' => filter_var($data['permissions']['event'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            return $this->userRepo->updateManager($user->id, [
                'parent_id'           => auth()->id(),
                'is_exclusive'        => filter_var($data['exclusive'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'manager_permissions'  => $permissions,
            ]);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getAvailableListByRole($role)
    {
        return $this->userRepo->getAvailableUsersByRole($role);
    }
}
