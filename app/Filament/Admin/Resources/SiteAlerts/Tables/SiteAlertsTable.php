<?php

namespace App\Filament\Admin\Resources\SiteAlerts\Tables;

use App\Filament\Admin\Resources\SiteAlerts\SiteAlertResource;
use App\Filament\Admin\Resources\Support\StandardTableActions;
use App\Models\SiteAlert;
use App\Models\WorkflowNotificationRule;
use App\Support\WorkflowNotificationService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class SiteAlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Alert label')
                    ->url(fn (SiteAlert $record): string => SiteAlertResource::getUrl('edit', ['record' => $record]))
                    ->placeholder('No label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(80)
                    ->html()
                    ->searchable(),
                TextColumn::make('link_label')
                    ->label('Link text')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('link_url')
                    ->label('Link destination')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tone')
                    ->label('Color')
                    ->formatStateUsing(fn (string $state): string => SiteAlert::toneLabels()[$state] ?? str($state)->headline())
                    ->badge()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('publish_at')
                    ->label('Publish at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_dismissible')
                    ->label('Dismissible')
                    ->boolean(),
                IconColumn::make('is_published')
                    ->label('Alert is live')
                    ->boolean()
                    ->tooltip(fn (SiteAlert $record): string => $record->is_published ? 'Unpublish alert' : 'Publish alert')
                    ->action(function (SiteAlert $record): void {
                        $record->update([
                            'is_published' => ! $record->is_published,
                        ]);

                        app(WorkflowNotificationService::class)->automaticForRecord(
                            $record,
                            WorkflowNotificationRule::TRIGGER_UPDATED,
                        );
                    })
                    ->sortable(),
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
}
