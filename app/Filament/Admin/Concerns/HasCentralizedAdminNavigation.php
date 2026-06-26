<?php

namespace App\Filament\Admin\Concerns;

use BackedEnum;

trait HasCentralizedAdminNavigation
{
    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = null;

    protected static ?string $navigationLabel = null;
}
