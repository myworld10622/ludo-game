<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('api_version', config('platform.api.default_version', 'v1'));

        return $next($request);
    }
}
