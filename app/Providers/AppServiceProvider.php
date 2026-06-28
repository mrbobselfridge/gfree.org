<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\AdminResetPassword;
use App\Support\AdminAccess;
use App\Support\NullSlideAnalyzer;
use App\Support\SlideAnalyzerInterface;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Tables\Table;
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
        $this->app->bind(SlideAnalyzerInterface::class, NullSlideAnalyzer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Table::configureUsing(fn (Table $table): Table => $table->defaultPaginationPageOption(50));

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (! request()->is('admin*')) {
                return null;
            }

            return AdminAccess::authorizeModelAbility($user, $ability, $arguments[0] ?? null);
        });
    }
}
