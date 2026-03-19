<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('platform.internal.api_token', '');

        if ($expectedToken === '') {
            abort(503, 'Internal API token is not configured.');
        }

        $providedToken = (string) ($request->header('X-Internal-Token')
            ?: $request->bearerToken()
            ?: $request->input('internal_token', ''));

        if (! hash_equals($expectedToken, $providedToken)) {
            abort(401, 'Invalid internal API token.');
        }

        return $next($request);
    }
}
