<?php

namespace App\Filament\Admin\Resources\Concerns;

use App\Support\AdminAccess;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

trait AppliesAdminAccess
{
    public static function getEloquentQuery(): Builder
    {
        return AdminAccess::scopeQuery(
            parent::getEloquentQuery(),
            Filament::auth()->user(),
            static::getModel(),
        );
    }
}
