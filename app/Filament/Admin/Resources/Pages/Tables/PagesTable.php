<?php

namespace App\Filament\Admin\Resources\Pages\Tables;

use App\Filament\Admin\Resources\Support\StandardTableActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('URL')
                    ->formatStateUsing(fn (string $state): string => '/'.ltrim($state, '/'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('hero_image_path')
                    ->disk('public'),
                TextColumn::make('seo_title')
                    ->label('SEO title')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Live')
                    ->boolean(),
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
            ->defaultSort('title')
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
