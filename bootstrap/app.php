<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum SPA cookie-based auth: requests from our SPA origin
        // (per SANCTUM_STATEFUL_DOMAINS) get the standard web auth stack.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API routes should always answer in JSON, including 401 — without this,
        // Laravel's default redirect-to-login behavior throws because we don't
        // define a named "login" route in this SPA setup.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 401);
            }
        });
    })->create();
