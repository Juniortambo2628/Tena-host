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
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'subscribed' => \App\Http\Middleware\EnsureUserIsSubscribed::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'staff' => \App\Http\Middleware\StaffOnly::class,
            'host' => \App\Http\Middleware\HostOnly::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            \Log::error('Unhandled exception: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
        $exceptions->renderable(function (\Throwable $e) {
            if (request()->expectsJson() || request()->header('X-Inertia') || request()->header('X-Requested-With')) {
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                if ($status < 400) {
                    $status = 500;
                }

                return response()->json(['error' => $e->getMessage()], $status);
            }
        });
    })->create();
