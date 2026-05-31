<?php

namespace App\Providers;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (! request()->is('admin*')) {
                return null;
            }

            return AdminAccess::authorizeModelAbility($user, $ability, $arguments[0] ?? null);
        });
    }
}
