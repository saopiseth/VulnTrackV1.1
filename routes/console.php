<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register custom commands
app()->resolving(\Illuminate\Console\Application::class, function ($artisan) {
    $artisan->resolveCommands([
        \App\Console\Commands\RebuildVulnTracking::class,
    ]);
});
