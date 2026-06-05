<?php

namespace App\Filament\Admin\Resources\WorkflowNotificationRules\Tables;

use App\Filament\Admin\Resources\Support\StandardTableActions;
use App\Support\WorkflowNotificationAreas;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class WorkflowNotificationRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('content_area')
                    ->label('Area')
                    ->formatStateUsing(fn (string $state): string => WorkflowNotificationAreas::options()[$state] ?? str($state)->headline()->toString())
                    ->sortable(),
                TextColumn::make('triggers')
                    ->badge()
                    ->formatStateUsing(fn (array|string|null $state): string => collect($state)->map(
                        fn (string $trigger): string => WorkflowNotificationAreas::triggerOptions()[$trigger] ?? str($trigger)->headline()->toString()
                    )->implode(', ')),
                TextColumn::make('delay_minutes')
                    ->label('Delay')
                    ->formatStateUsing(fn (int $state): string => WorkflowNotificationAreas::delayOptions()[$state] ?? "{$state} minutes"),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
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
