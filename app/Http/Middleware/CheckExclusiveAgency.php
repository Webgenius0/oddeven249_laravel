<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

class CheckExclusiveAgency
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->role === 'influencer') {
            $hasExclusiveAgency = $user->agencies()
                ->wherePivot('is_exclusive', true)
                ->exists();

            if ($hasExclusiveAgency) {
                return $this->error(
                    ['is_exclusive' => true],
                    'You have assigned an exclusive agency. All deals and chats must be managed by your agency.',
                    403
                );
            }
        }

        return $next($request);
    }
}
