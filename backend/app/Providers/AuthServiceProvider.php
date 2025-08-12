<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
     
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('access-admin-panel', function ($user) {
            return $user->hasAnyRole(['admin', 'instructor']);
        });

        Gate::define('access-user-panel', function ($user) {
            return $user->hasAnyRole(['student', 'instructor', 'admin']);
        });

        Gate::guessPolicyNamesUsing(function ($modelClass) {
            return null;
        });
    }
}