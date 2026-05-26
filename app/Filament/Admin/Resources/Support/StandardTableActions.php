<?php

namespace App\Filament\Admin\Resources\Support;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StandardTableActions
{
    public static function make(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->iconButton()
                ->icon(Heroicon::OutlinedPencilSquare),

            Action::make('copy')
                ->label('Copy')
                ->icon(Heroicon::OutlinedSquare2Stack)
                ->iconButton()
                ->action(function (Model $record): void {
                    $copy = $record->replicate();
                    $labelField = self::labelField($record);
                    $timestamp = now();

                    if ($labelField) {
                        $copy->{$labelField} = sprintf(
                            '%s (copy @ %s)',
                            $record->{$labelField},
                            $timestamp->format('Y-m-d H:i:s'),
                        );
                    }

                    if (array_key_exists('slug', $record->getAttributes())) {
                        $copy->slug = self::uniqueSlug(
                            $record,
                            filled($record->slug) ? $record->slug : ($labelField ? $record->{$labelField} : null),
                        );
                    }

                    $copy->save();

                    Notification::make()
                        ->success()
                        ->title('Copied')
                        ->body(self::recordLabel($record).' was copied.')
                        ->send();
                }),

            DeleteAction::make()
                ->label('Delete')
                ->iconButton()
                ->icon(Heroicon::OutlinedTrash)
                ->modalHeading('Delete item?')
                ->modalDescription(fn (Model $record): string => 'Are you sure you want to delete: '.self::recordLabel($record).'?')
                ->modalSubmitActionLabel('Yes')
                ->modalCancelActionLabel('No'),
        ];
    }

    public static function recordLabel(Model $record): string
    {
        $field = self::labelField($record);

        if ($field && filled($record->{$field})) {
            return (string) $record->{$field};
        }

        return class_basename($record).' #'.$record->getKey();
    }

    private static function labelField(Model $record): ?string
    {
        foreach (['title', 'name', 'label', 'church_name'] as $field) {
            if (array_key_exists($field, $record->getAttributes())) {
                return $field;
            }
        }

        return null;
    }

    private static function uniqueSlug(Model $record, ?string $source): string
    {
        $base = Str::slug((string) $source);

        if (blank($base)) {
            $base = Str::slug(self::recordLabel($record));
        }

        $base = Str::limit($base, 220, '');
        $slug = "{$base}-copy-".now()->timestamp;
        $candidate = $slug;
        $counter = 2;

        while ($record->newQuery()->where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}
