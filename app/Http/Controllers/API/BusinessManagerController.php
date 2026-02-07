<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Services\UserService; // Service ইমপোর্ট করুন
use Illuminate\Support\Facades\Validator; // Validator ইমপোর্ট নিশ্চিত করুন

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
            'country'     => 'nullable|string|max:100',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $manager = $this->userService->assignExistingManager($request->all());

            return $this->success($manager, 'Business Manager assigned and updated successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function storeAgency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'exclusive'   => 'nullable|boolean',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $agency = $this->userService->assignAgency($request->all());
            return $this->success($agency, 'Agency assigned and updated successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    public function getAvailableUsers(Request $request)
    {

        $role = $request->query('role', 'business_manager');

        $users = $this->userService->getAvailableListByRole($role);

        return $this->success($users, ucfirst($role) . 's fetched for dropdown.');
    }
}
