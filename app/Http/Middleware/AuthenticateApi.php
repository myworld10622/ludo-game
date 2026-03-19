<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = config('platform.auth.api_guard', 'sanctum');
        Auth::shouldUse($guard);

        if (! Auth::guard($guard)->check()) {
            return ApiResponse::error('Unauthenticated.', [
                'auth' => ['Authentication is required for this endpoint.'],
            ], 401);
        }

        return $next($request);
    }
}
