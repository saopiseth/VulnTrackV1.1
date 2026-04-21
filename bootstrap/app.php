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
    ->withCommands([
        \App\Console\Commands\RebuildVulnTracking::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware: security headers on every response
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Named middleware aliases
        $middleware->alias([
            'agent.token' => \App\Http\Middleware\VerifyAgentToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
