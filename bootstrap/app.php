<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'ussd/*',
            'payment/*',
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'ensure_kyc_tier' => \App\Http\Middleware\EnsureKycTier::class,
            'log.admin' => \App\Http\Middleware\LogAdminActions::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->prepend(\App\Http\Middleware\TrustProxies::class);

        $middleware->web(\App\Http\Middleware\SetLocale::class);

        // Redis throttling handled per-route in web.php and api.php
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            if (class_exists(Integration::class) && app()->bound('sentry')) {
                Integration::captureUnhandledException($e);
            }
        });
    })->create();
