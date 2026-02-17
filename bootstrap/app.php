<?php

use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        then: function () {

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/mizan_api.php'));

            Route::middleware(['web', 'auth', 'verified']) // Apply middleware
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware(['web'])
                ->group(base_path('routes/frontend.php'));

            Route::middleware('api') //api
                ->prefix('api')
                ->group(base_path('routes/chat_api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware alias
        $middleware->alias([
            'role'             => \App\Http\Middleware\CheckRole::class,
            'check_permission' => \App\Http\Middleware\CheckManagerPermission::class,
            'last_seen'        => UpdateLastSeen::class,

        ]);

        $middleware->validateCsrfTokens(except: [
            // 'api*',
            'broadcasting/auth',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Run every minute
        $schedule->command('conversations:auto-unmute')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
