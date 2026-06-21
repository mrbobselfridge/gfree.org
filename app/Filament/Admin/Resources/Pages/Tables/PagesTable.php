<?php

namespace App\Filament\Admin\Resources\Pages\Tables;

use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\Pages\Schemas\PageForm;
use App\Filament\Admin\Resources\Support\StandardTableActions;
use App\Models\Page;
use App\Models\WorkflowNotificationRule;
use App\Support\WorkflowNotificationService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
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
                    ->label('Page title')
                    ->url(fn (Page $record): string => PageResource::getUrl('edit', ['record' => $record]))
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
                    ->label('Page path')
                    ->formatStateUsing(fn (string $state): string => '/'.ltrim($state, '/'))
                    ->url(fn ($record): string => url('/'.ltrim((string) $record->slug, '/')))
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parentPage.title')
                    ->label('Parent page')
                    ->url(fn (Page $record): ?string => filled($record->parent_page_id)
                        ? self::pageHierarchyFilterUrl((int) $record->parent_page_id)
                        : null)
                    ->icon(fn (Page $record): ?Heroicon => filled($record->parent_page_id)
                        ? Heroicon::OutlinedFunnel
                        : null)
                    ->iconColor('warning')
                    ->extraAttributes(fn (Page $record): array => filled($record->parent_page_id)
                        ? self::actionableHierarchyAttributes()
                        : [])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => self::applyParentPageSort($query, $direction))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('redirect_url')
                    ->label('Redirects to')
                    ->placeholder('Not a redirect')
                    ->limit(44)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->tooltip(fn (Page $record): string => $record->is_published ? 'Turn off live page' : 'Turn on live page')
                    ->action(function (Page $record): void {
                        $record->update([
                            'is_published' => ! $record->is_published,
                        ]);

                        app(WorkflowNotificationService::class)->automaticForRecord(
                            $record,
                            WorkflowNotificationRule::TRIGGER_UPDATED,
                        );
                    })
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
                SelectFilter::make('parent_page_id')
                    ->label('Parent page')
                    ->options(fn (): array => PageForm::parentPageOptions())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where(fn (Builder $query): Builder => $query
                            ->whereKey($data['value'])
                            ->orWhere('parent_page_id', $data['value']))
                        : $query)
                    ->searchable()
                    ->preload(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('childPages'))
            ->defaultSort('title')
            ->persistFiltersInSession()
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

    private static function applyParentPageSort(Builder $query, string $direction): Builder
    {
        $parentTitleQuery = Page::query()
            ->select('parent_pages.title')
            ->from('pages as parent_pages')
            ->whereColumn('parent_pages.id', 'pages.parent_page_id')
            ->limit(1);

        return $query
            ->orderByRaw('CASE WHEN pages.parent_page_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy($parentTitleQuery, $direction === 'desc' ? 'desc' : 'asc')
            ->orderBy('pages.sort_order')
            ->orderBy('pages.title');
    }

    private static function pageHierarchyFilterUrl(int|string $pageId): string
    {
        return PageResource::getUrl('index', [
            'filters' => [
                'parent_page_id' => [
                    'value' => $pageId,
                ],
            ],
            'sort' => 'parentPage.title:asc',
        ]);
    }

    private static function actionableHierarchyAttributes(): array
    {
        return [
            'class' => 'underline underline-offset-4 decoration-warning-500/70 hover:decoration-warning-500',
        ];
    }
}
