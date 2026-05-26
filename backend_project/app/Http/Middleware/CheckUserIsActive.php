<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {

            // Force logout the blocked user
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact the system administrator.'
            ], 403);
        }

        return $next($request);
    }
}
