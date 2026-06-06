<?php

namespace App\Filament\Admin\Resources\FileDocuments\RelationManagers;

use App\Filament\Admin\Support\IconOnlyAction;
use App\Models\FileDocument;
use App\Models\FileDocumentVersion;
use App\Support\FileLibrary;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Version History';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof FileDocument && $ownerRecord->exists;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('current')
                    ->label('Current')
                    ->boolean()
                    ->state(fn (FileDocumentVersion $record): bool => $record->getKey() === $this->getOwnerRecord()->current_version_id),
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('extension')
                    ->label('Type')
                    ->badge()
                    ->placeholder('Unknown'),
                TextColumn::make('size')
                    ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : Number::fileSize($state)),
                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded by')
                    ->placeholder('Unknown'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                IconOnlyAction::make(
                    Action::make('downloadVersion')
                        ->label('Download')
                        ->url(fn (FileDocumentVersion $record): string => route('admin.files.versions.download', ['fileDocumentVersion' => $record]), true),
                    Heroicon::OutlinedArrowDownTray,
                ),
                IconOnlyAction::make(
                    Action::make('restoreVersion')
                        ->label('Restore')
                        ->requiresConfirmation()
                        ->modalHeading('Restore this file version?')
                        ->modalDescription('This makes the selected version the current download. The other versions stay in history.')
                        ->visible(fn (FileDocumentVersion $record): bool => $record->getKey() !== $this->getOwnerRecord()->current_version_id)
                        ->action(function (FileDocumentVersion $record): void {
                            FileLibrary::makeCurrent($this->getOwnerRecord(), $record, Filament::auth()->user());

                            Notification::make()
                                ->title('Version restored')
                                ->success()
                                ->send();
                        }),
                    Heroicon::OutlinedArrowPath,
                ),
                IconOnlyAction::make(
                    DeleteAction::make('deleteVersion')
                        ->label('Delete')
                        ->modalHeading('Delete this file version?')
                        ->modalDescription('This removes the selected old version and its stored file. The current version cannot be deleted here.')
                        ->visible(fn (FileDocumentVersion $record): bool => $record->getKey() !== $this->getOwnerRecord()->current_version_id),
                    Heroicon::OutlinedTrash,
                ),
            ]);
    }
}
