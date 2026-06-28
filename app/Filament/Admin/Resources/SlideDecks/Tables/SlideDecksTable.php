<?php

namespace App\Filament\Admin\Resources\SlideDecks\Tables;

use App\Filament\Admin\Resources\SlideDecks\SlideDeckResource;
use App\Filament\Admin\Resources\Support\StandardTableActions;
use App\Models\SlideDeck;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SlideDecksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Deck')
                    ->url(fn (SlideDeck $record): string => SlideDeckResource::getUrl('edit', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SlideDeck::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        SlideDeck::STATUS_COMPLETED => 'success',
                        SlideDeck::STATUS_FAILED => 'danger',
                        SlideDeck::STATUS_PROCESSING => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total_slides')
                    ->label('Slides')
                    ->sortable(),
                TextColumn::make('processed_slides')
                    ->label('Processed')
                    ->sortable(),
                TextColumn::make('original_filename')
                    ->label('Original file')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('createdBy.name')
                    ->label('Created by')
                    ->placeholder('Unknown')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SlideDeck::statuses()),
            ])
            ->defaultSort('updated_at', 'desc')
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
