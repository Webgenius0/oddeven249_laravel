<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckManagerPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

   
        $influencerId = $request->header('X-Influencer-Id') ?: $request->input('influencer_id');

        if (!$influencerId || $user->id == $influencerId) {
            return $next($request);
        }
        $assignment = DB::table('business_manager_assignments')
            ->where('user_id', $influencerId)  
            ->where('manager_id', $user->id)   
            ->first();

        if (!$assignment) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: You are not assigned to this influencer.'
            ], 403);
        }

        $assignedPermissions = json_decode($assignment->permissions, true) ?: [];

        foreach ($permissions as $p) {
            if (isset($assignedPermissions[$p]) && ($assignedPermissions[$p] === true || $assignedPermissions[$p] === 1)) {
                return $next($request);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Permission denied. Required: ' . implode(' or ', $permissions)
        ], 403);
    }
}
