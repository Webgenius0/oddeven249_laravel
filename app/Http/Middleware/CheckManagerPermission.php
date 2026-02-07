<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckManagerPermission
{
    /**
     * Handle an incoming request.
     * * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();

        // 1. user login check
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        // 2. full access if boss
        if ($user->parent_id === null) {
            return $next($request);
        }

        // 3. Manager permission data
        $userManagerPermissions = $user->manager_permissions ?? [];

        // 4. If any permission valid then true
        foreach ($permissions as $p) {
            if (isset($userManagerPermissions[$p]) && ($userManagerPermissions[$p] === true || $userManagerPermissions[$p] === 1)) {
                return $next($request);
            }
        }
        return response()->json([
            'status' => false,
            'message' => 'You do not have permission to perform this action. Required: ' . implode(' or ', $permissions)
        ], 403);
    }
}
