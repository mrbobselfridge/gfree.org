<?php

namespace App\Filament\Admin\Resources\Pages\Tables;

use App\Filament\Admin\Resources\Support\StandardTableActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('is_redirect')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Redirect' : 'Page')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success'),
                TextColumn::make('slug')
                    ->formatStateUsing(fn (string $state): string => '/'.ltrim($state, '/'))
                    ->url(fn ($record): string => url('/'.ltrim((string) $record->slug, '/')))
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('redirect_url')
                    ->label('Redirects To')
                    ->placeholder('Not a redirect')
                    ->limit(44)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('parentPage.title')
                    ->label('Parent Page')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                ImageColumn::make('hero_image_path')
                    ->disk('public')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('card_image_path')
                    ->label('Card image')
                    ->disk('public')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('seo_title')
                    ->label('SEO title')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->label('Live')
                    ->boolean(),
                TextColumn::make('publish_at')
                    ->label('Publish at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires at')
                    ->dateTime()
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
                SelectFilter::make('page_type')
                    ->label('Page type')
                    ->options([
                        'page' => 'Content pages',
                        'redirect' => 'Redirect pages',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'page' => $query->where('is_redirect', false),
                        'redirect' => $query->where('is_redirect', true),
                        default => $query,
                    }),
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
