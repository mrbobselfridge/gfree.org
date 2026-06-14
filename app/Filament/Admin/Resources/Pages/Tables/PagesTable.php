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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_redirect')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Redirect' : 'Page')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success')
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::applyBooleanSearch(
                        query: $query,
                        search: $search,
                        column: 'is_redirect',
                        trueTerms: ['redirect', 'redirects', 'forward', 'forwarding', '1', 'yes', 'true'],
                        falseTerms: ['page', 'pages', 'content', 'content page', 'content pages', '0', 'no', 'false'],
                    ))
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Path')
                    ->formatStateUsing(fn (string $state): string => '/'.ltrim($state, '/'))
                    ->url(fn ($record): string => url('/'.ltrim((string) $record->slug, '/')))
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parentPage.title')
                    ->label('Parent Page')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('redirect_url')
                    ->label('Redirects To')
                    ->placeholder('Not a redirect')
                    ->limit(44)
                    ->searchable()
                    ->toggleable(),
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
                    ->boolean()
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::applyBooleanSearch(
                        query: $query,
                        search: $search,
                        column: 'is_published',
                        trueTerms: ['live', 'published', 'active', '1', 'yes', 'true'],
                        falseTerms: ['draft', 'unpublished', 'inactive', '0', 'no', 'false'],
                    ))
                    ->sortable(),
                TextColumn::make('publish_at')
                    ->label('Publish at')
                    ->dateTime()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires at')
                    ->dateTime()
                    ->searchable()
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
            ->defaultSort('title')
            ->persistSortInSession()
            ->persistColumnsInSession()
            ->recordAction(null)
            ->recordUrl(null)
            ->recordActions(StandardTableActions::make(), position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function applyBooleanSearch(
        Builder $query,
        string $search,
        string $column,
        array $trueTerms,
        array $falseTerms,
    ): Builder {
        $search = Str::of($search)->lower()->trim()->toString();

        if (in_array($search, $trueTerms, true)) {
            return $query->where($column, true);
        }

        if (in_array($search, $falseTerms, true)) {
            return $query->where($column, false);
        }

        return $query->whereRaw('0 = 1');
    }
}
