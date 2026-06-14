<?php

namespace App\Filament\Admin\Forms;

use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class SlugRebuildAction
{
    public static function make(string $sourceField, string $targetField = 'slug'): Action
    {
        return Action::make('rebuild'.Str::studly($targetField))
            ->label('Generate path')
            ->tooltip('Generate path')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->action(fn (Get $get, Set $set): mixed => $set($targetField, Str::slug((string) $get($sourceField))));
    }
}
