<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // 1. Make API routes stateful (required for Sanctum)
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // 2. Disable CSRF protection for all API routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'sanctum/csrf-cookie',
            'employee/*',
            'patient/*',
        ]);

        $middleware->alias([
            'active' => \App\Http\Middleware\CheckUserIsActive::class,
            'stateful' => $middleware->statefulApi(),

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
