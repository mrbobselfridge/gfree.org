<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Tables;

use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Admin\Resources\Support\StandardTableActions;
use App\Models\NavigationLink;
use App\Models\WorkflowNotificationRule;
use App\Support\WorkflowNotificationService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NavigationLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Link text')
                    ->url(fn (NavigationLink $record): string => NavigationLinkResource::getUrl('edit', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->label('Destination')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Location')
                    ->formatStateUsing(fn (string $state): string => NavigationLink::locationOptions()[$state] ?? str($state)->headline())
                    ->badge()
                    ->sortable(),
                TextColumn::make('parent.label')
                    ->label('Parent link')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => self::applyParentSort($query, $direction)),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('publish_at')
                    ->label('Publish at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expires_at')
                    ->label('Expires at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('opens_in_new_tab')
                    ->label('New tab')
                    ->boolean(),
                IconColumn::make('is_published')
                    ->label('Link is live')
                    ->boolean()
                    ->tooltip(fn (NavigationLink $record): string => $record->is_published ? 'Unpublish link' : 'Publish link')
                    ->action(function (NavigationLink $record): void {
                        $record->update([
                            'is_published' => ! $record->is_published,
                        ]);

                        app(WorkflowNotificationService::class)->automaticForRecord(
                            $record,
                            WorkflowNotificationRule::TRIGGER_UPDATED,
                        );
                    })
                    ->sortable(),
                TextColumn::make('page_limit')
                    ->label('Page limits')
                    ->state(fn (NavigationLink $record): string => $record->pageLimitLabel())
                    ->description(fn (NavigationLink $record): ?string => $record->pageLimitDescription())
                    ->badge()
                    ->color(fn (NavigationLink $record): string => $record->pageLimitColor()),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('sort_order')
            ->recordAction(null)
            ->recordUrl(null)
            ->recordActions(StandardTableActions::make(), position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function applyParentSort(Builder $query, string $direction): Builder
    {
        $parentLabelQuery = NavigationLink::query()
            ->select('parent_navigation_links.label')
            ->from('navigation_links as parent_navigation_links')
            ->whereColumn('parent_navigation_links.id', 'navigation_links.parent_id')
            ->limit(1);

        return $query
            ->orderByRaw('CASE WHEN navigation_links.parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy($parentLabelQuery, $direction === 'desc' ? 'desc' : 'asc')
            ->orderBy('navigation_links.sort_order')
            ->orderBy('navigation_links.label');
    }
}
