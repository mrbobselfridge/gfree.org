<?php

namespace App\Filament\Admin\Resources\FileCategories\Tables;

use App\Filament\Admin\Support\IconOnlyAction;
use App\Models\FileCategory;
use App\Models\FileDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class FileCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('files_count')
                    ->label('Files')
                    ->state(fn (FileCategory $record): int => FileDocument::query()
                        ->where('category', $record->name)
                        ->count()),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->recordAction(null)
            ->recordUrl(null)
            ->recordActions([
                IconOnlyAction::make(
                    EditAction::make()
                        ->label('Edit'),
                    Heroicon::OutlinedPencilSquare,
                ),
                IconOnlyAction::make(
                    DeleteAction::make()
                        ->label('Delete')
                        ->modalDescription(fn (FileCategory $record): string => self::deleteDescription($record)),
                    Heroicon::OutlinedTrash,
                ),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function deleteDescription(FileCategory $record): string
    {
        $count = FileDocument::query()
            ->where('category', $record->name)
            ->count();

        if ($count === 0) {
            return 'Remove this category from the managed list?';
        }

        return "Remove this category from the managed list? {$count} existing ".str('file')->plural($count).' will keep this category text until edited.';
    }
}
