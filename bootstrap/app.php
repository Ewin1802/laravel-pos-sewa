<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function ($schedule): void {
        // Daily at 2:00 AM - Mark expired subscriptions
        $schedule->command('subscriptions:mark-expired')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Daily at 2:30 AM - Mark overdue invoices as expired
        $schedule->command('invoices:mark-overdue')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground();

        // Daily at 9:00 AM - Send expiration notifications
        $schedule->command('notifications:expiration-alerts')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
