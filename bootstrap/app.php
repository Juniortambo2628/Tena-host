<?php

use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\EnsureUserIsSubscribed;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HostOnly;
use App\Http\Middleware\StaffOnly;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'subscribed' => EnsureUserIsSubscribed::class,
            'admin' => AdminOnly::class,
            'staff' => StaffOnly::class,
            'host' => HostOnly::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'admin/landing/sections/*/media',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e) {
            Log::error('Unhandled exception: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });

        $exceptions->renderable(function (Throwable $e) {
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($status < 400) {
                $status = 500;
            }

            // Validation exceptions → always redirect back with errors (Inertia, JSON, and standard web)
            if ($e instanceof ValidationException) {
                if (request()->header('X-Inertia') || request()->expectsJson() || request()->header('X-Requested-With')) {
                    return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
                }
                return back()->withErrors($e->errors())->withInput();
            }

            // JSON / AJAX requests
            if (request()->expectsJson() || request()->header('X-Requested-With')) {
                if ($e instanceof AuthenticationException) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
                return response()->json(['error' => $e->getMessage()], $status);
            }

            // Unauthenticated web users → redirect to login
            if ($e instanceof AuthenticationException) {
                return redirect()->route('login');
            }

            // Standard web requests → render Blade error page
            $titles = [
                404 => 'Page Not Found',
                403 => 'Access Denied',
                405 => 'Method Not Allowed',
                419 => 'Session Expired',
                429 => 'Too Many Requests',
                500 => 'Server Error',
                503 => 'Maintenance Mode',
            ];

            return response()->view('errors.'.$status, [
                'status' => $status,
                'title' => $titles[$status] ?? 'Error',
                'message' => $e->getMessage(),
            ], $status);
        });
    })->create();
