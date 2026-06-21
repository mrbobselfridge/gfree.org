<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Tables;

use App\Filament\Admin\Resources\Support\StandardTableActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class HomepageBannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Banner title')
                    ->searchable(),
                TextColumn::make('eyebrow')
                    ->label('Small label')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('image_path')
                    ->label('Banner image')
                    ->disk('public'),
                TextColumn::make('button_label')
                    ->label('Primary button text')
                    ->searchable(),
                TextColumn::make('button_url')
                    ->label('Primary button destination')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('secondary_button_label')
                    ->label('Secondary button text')
                    ->searchable(),
                TextColumn::make('secondary_button_url')
                    ->label('Secondary button destination')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('starts_at')
                    ->label('Publish at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Expires at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Banner is live')
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
