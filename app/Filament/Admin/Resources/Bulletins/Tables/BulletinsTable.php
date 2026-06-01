<?php

namespace App\Filament\Admin\Resources\Bulletins\Tables;

use App\Filament\Admin\Resources\Support\StandardTableActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class BulletinsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bulletin_date')
                    ->label('Bulletin date')
                    ->date()
                    ->url(fn ($record): ?string => $record->bulletin_date ? url('/bulletins/'.$record->bulletin_date->toDateString()) : null)
                    ->openUrlInNewTab()
                    ->sortable(),
                TextColumn::make('pdf_path')
                    ->label('PDF')
                    ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : 'No PDF')
                    ->toggleable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('bulletin_date', 'desc')
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
