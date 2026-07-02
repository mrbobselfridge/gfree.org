<?php

namespace App\Filament\Admin\Support;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class NotesAction
{
    public static function make(): Action
    {
        return IconOnlyAction::make(
            Action::make('jumpToNotes')
                ->label('Notes')
                ->url('#notes')
                ->color('gray')
                ->keyBindings(['alt+n']),
            Heroicon::OutlinedDocumentText,
            'Notes (Alt+N)',
        );
    }

    public static function forRecord(?Model $record): ?Action
    {
        if (! $record || ! Schema::hasColumn($record->getTable(), 'notes')) {
            return null;
        }

        return self::make();
    }

    public static function forRecordActions(?Model $record): array
    {
        $action = self::forRecord($record);

        return $action ? [$action] : [];
    }
}
