<?php

namespace App\Filament\Admin\Resources\FileDocuments\Tables;

use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Models\FileDocument;
use App\Models\FileDocumentVersion;
use App\Models\WorkflowNotificationRule;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Js;
use Illuminate\Support\Number;

class FileDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('card_image_path')
                    ->label('Card image')
                    ->state(fn (FileDocument $record): string => $record->cardImageUrl())
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('File title')
                    ->url(fn (FileDocument $record): string => FileDocumentResource::getUrl('edit', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('file_name')
                    ->label('File path')
                    ->formatStateUsing(fn (FileDocument $record): string => $record->publicUrl() ?? 'Not live')
                    ->url(fn (FileDocument $record): ?string => $record->publicUrl(), true)
                    ->copyable(fn (FileDocument $record): bool => filled($record->publicUrl()))
                    ->copyableState(fn (FileDocument $record): ?string => $record->publicUrl())
                    ->copyMessage('Public link copied')
                    ->searchable(),
                TextColumn::make('category')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parentPage.title')
                    ->label('Parent page')
                    ->placeholder('None')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label('Sort order')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_published')
                    ->label('Live')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('visibility')
                    ->label('Visibility')
                    ->boolean()
                    ->state(fn (FileDocument $record): bool => $record->visibility === FileDocument::VISIBILITY_PUBLIC),
                TextColumn::make('currentVersion.extension')
                    ->label('Type')
                    ->badge()
                    ->placeholder('None')
                    ->toggleable(),
                TextColumn::make('currentVersion.size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : Number::fileSize($state))
                    ->placeholder('None')
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('publish_at')
                    ->label('Publish at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Immediate'),
                TextColumn::make('updatedBy.name')
                    ->label('Updated by')
                    ->placeholder('Unknown')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded by')
                    ->placeholder('Unknown')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => FileDocument::categoryOptions()),
                SelectFilter::make('visibility')
                    ->options([
                        FileDocument::VISIBILITY_PUBLIC => 'Public',
                        FileDocument::VISIBILITY_PRIVATE => 'Private',
                    ]),
                SelectFilter::make('file_type')
                    ->label('File type')
                    ->options(fn (): array => FileDocumentVersion::query()
                        ->whereNotNull('extension')
                        ->distinct()
                        ->orderBy('extension')
                        ->pluck('extension', 'extension')
                        ->all())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('currentVersion', fn ($versionQuery) => $versionQuery->where('extension', $data['value']))
                        : $query),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordAction(null)
            ->recordUrl(null)
            ->recordActions(self::actions(), position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function actions(): array
    {
        return [
            IconOnlyAction::make(
                EditAction::make()
                    ->label('Edit')
                    ->url(fn (FileDocument $record): string => FileDocumentResource::getUrl('edit', ['record' => $record])),
                Heroicon::OutlinedPencilSquare,
            ),
            IconOnlyAction::make(
                Action::make('download')
                    ->label('View')
                    ->url(fn (FileDocument $record): string => $record->downloadUrl(), true),
                Heroicon::OutlinedArrowDownTray,
            ),
            IconOnlyAction::make(
                Action::make('copyPublicLink')
                    ->label('Copy link')
                    ->alpineClickHandler(fn (FileDocument $record): string => 'window.navigator.clipboard.writeText('.Js::from($record->publicUrl()).')')
                    ->hidden(fn (FileDocument $record): bool => blank($record->publicUrl())),
                Heroicon::OutlinedClipboardDocument,
            ),
            IconOnlyAction::make(
                DeleteAction::make()
                    ->label('Delete')
                    ->after(fn (FileDocument $record): mixed => app(WorkflowNotificationService::class)->automaticForRecord(
                        $record,
                        WorkflowNotificationRule::TRIGGER_DELETED,
                    )),
                Heroicon::OutlinedTrash,
            ),
        ];
    }
}
