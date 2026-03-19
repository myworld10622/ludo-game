<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\AdminRoleMiddleware;
use App\Http\Middleware\ApiVersion;
use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\VerifyInternalApiToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require base_path('routes/admin.php');
            require base_path('routes/admin_web.php');
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'api.auth' => AuthenticateApi::class,
            'admin.auth' => AdminAuthenticate::class,
            'admin.role' => AdminRoleMiddleware::class,
            'api.version' => ApiVersion::class,
            'internal.api' => VerifyInternalApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
require_once __DIR__.'/../routes/internal_tournaments.php';
