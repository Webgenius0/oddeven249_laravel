<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Services\UserService;
use Illuminate\Support\Facades\Validator;

class BusinessManagerController extends Controller
{
    use ApiResponse;

    protected $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function storeManager(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $manager = $this->userService->assignExistingManager($request->all());
            return $this->success($manager, 'Business Manager assigned successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    public function storeAgency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $agency = $this->userService->assignAgency($request->all());
            return $this->success($agency, 'Agency assigned successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    // ড্রপডাউনের জন্য এভেইল্যাবল ইউজারদের লিস্ট
    public function getAvailableUsers(Request $request)
    {
        $role = $request->query('role', 'business_manager');
        $users = $this->userService->getAvailableListByRole($role);
        return $this->success($users, ucfirst($role) . 's fetched for dropdown.');
    }

    public function getMyConnectedClients()
    {
        try {
            $clients = auth()->user()->clients()->select('users.id', 'users.name', 'users.avatar')->get();
            return $this->success($clients, 'Connected influencers fetched successfully.');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function getMyAgencies()
    {
        try {
            $agencies = $this->userService->getMyAssignedTeamByRole(
                auth()->user(),
                \App\Models\User::ROLE_AGENCY
            );

            return $this->success($agencies, 'Your assigned agencies fetched successfully.');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function getMyManagers()
    {
        try {
            $managers = $this->userService->getMyAssignedTeamByRole(
                auth()->user(),
                \App\Models\User::ROLE_BUSINESS_MANAGER
            );

            return $this->success($managers, 'Your assigned business managers fetched successfully.');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
}
