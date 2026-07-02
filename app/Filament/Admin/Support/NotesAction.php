<?php

namespace App\Filament\Admin\Support;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class NotesAction
{
    public static function make(string $name = 'jumpToNotes', bool $withShortcut = true): Action
    {
        $action = Action::make($name)
            ->label('Notes')
            ->url('#notes')
            ->color('warning');

        if ($withShortcut) {
            $action->keyBindings(['alt+n']);
        }

        return IconOnlyAction::make(
            $action,
            Heroicon::OutlinedDocumentText,
            $withShortcut ? 'Notes (Alt+N)' : 'Notes',
        );
    }

    public static function forRecord(?Model $record, string $name = 'jumpToNotes', bool $withShortcut = true): ?Action
    {
        if (! $record || ! Schema::hasColumn($record->getTable(), 'notes')) {
            return null;
        }

        return self::make($name, $withShortcut);
    }

    public static function forRecordActions(?Model $record, string $name = 'jumpToNotes', bool $withShortcut = true): array
    {
        $action = self::forRecord($record, $name, $withShortcut);

        return $action ? [$action] : [];
    }
}
