<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\AdminResetPassword;
use App\Support\AdminAccess;
use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ResetPassword::class, fn ($app, array $parameters): AdminResetPassword => new AdminResetPassword($parameters['token']));
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
