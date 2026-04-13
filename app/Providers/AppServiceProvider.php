<?php

namespace App\Providers;

use App\Models\ProjectAssessment;
use App\Models\User;
use App\Policies\ProjectAssessmentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register policies explicitly
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ProjectAssessment::class, ProjectAssessmentPolicy::class);

        // Redirect 403 with a flash message instead of error page
        $this->app['router']->bind('user', function ($value) {
            return User::findOrFail($value);
        });
    }
}
