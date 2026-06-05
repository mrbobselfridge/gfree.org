<?php

namespace App\Filament\Admin\Resources\Support;

use App\Filament\Admin\Support\PublicPageActions;
use App\Models\NavigationLink;
use App\Models\WorkflowNotificationRule;
use App\Support\AdminAccess;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
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
                ->authorize(fn (Model $record): bool => self::canCopy($record))
                ->action(function (Model $record): void {
                    $copy = $record->replicate();
                    $labelField = self::labelField($record);
                    $timestamp = now();

                    if ($labelField) {
                        $copy->{$labelField} = self::copyLabel($record, $labelField, $timestamp->format('Y-m-d H:i:s'));
                    }

                    if (array_key_exists('slug', $record->getAttributes())) {
                        $copy->slug = self::uniqueSlug(
                            $record,
                            filled($record->slug) ? $record->slug : ($labelField ? $record->{$labelField} : null),
                        );
                    }

                    $copy->save();
                    self::copyRelatedRecords($record, $copy);

                    app(WorkflowNotificationService::class)->automaticForRecord(
                        $copy,
                        WorkflowNotificationRule::TRIGGER_CREATED,
                    );

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
                ->modalCancelActionLabel('No')
                ->after(fn (Model $record): mixed => app(WorkflowNotificationService::class)->automaticForRecord(
                    $record,
                    WorkflowNotificationRule::TRIGGER_DELETED,
                )),

            PublicPageActions::tableAction(),
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

    private static function canCopy(Model $record): bool
    {
        $user = Filament::auth()->user();

        if ($user) {
            $adminAccess = AdminAccess::authorizeModelAbility($user, 'create', $record::class);

            if ($adminAccess !== null) {
                return $adminAccess;
            }
        }

        return Gate::allows('create', $record::class);
    }

    private static function copyLabel(Model $record, string $field, string $timestamp): string
    {
        $suffix = " (copy @ {$timestamp})";
        $source = (string) $record->{$field};
        $maxLength = self::stringColumnLength($record, $field) ?? 255;
        $baseLength = max(1, $maxLength - strlen($suffix));

        return Str::limit($source, $baseLength, '').$suffix;
    }

    private static function copyRelatedRecords(Model $record, Model $copy): void
    {
        if (! $record instanceof NavigationLink || ! $copy instanceof NavigationLink) {
            return;
        }

        self::copyNavigationChildren($record, $copy);
    }

    private static function copyNavigationChildren(NavigationLink $sourceParent, NavigationLink $copyParent): void
    {
        $sourceParent->children()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->each(function (NavigationLink $child) use ($copyParent): void {
                $childCopy = $child->replicate();
                $childCopy->parent_id = $copyParent->id;
                $childCopy->save();

                self::copyNavigationChildren($child, $childCopy);
            });
    }

    private static function stringColumnLength(Model $record, string $field): ?int
    {
        $connection = $record->getConnection();
        $columns = $connection->getSchemaBuilder()->getColumns($record->getTable());
        $column = collect($columns)->firstWhere('name', $field);

        return is_numeric($column['length'] ?? null) ? (int) $column['length'] : null;
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
