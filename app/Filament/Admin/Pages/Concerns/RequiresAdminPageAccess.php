<?php

namespace App\Filament\Admin\Pages\Concerns;

use App\Support\AdminAccess;
use Filament\Facades\Filament;

trait RequiresAdminPageAccess
{
    public static function canAccess(): bool
    {
        return AdminAccess::canAccessPage(Filament::auth()->user(), static::class);
    }
}
