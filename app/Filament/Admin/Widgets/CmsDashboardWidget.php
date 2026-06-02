<?php

namespace App\Filament\Admin\Widgets;

use App\Support\AdminAccess;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

abstract class CmsDashboardWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.cms-list-widget';

    protected int|string|array $columnSpan = 1;

    protected static bool $isLazy = false;

    abstract protected function heading(): string;

    abstract protected function description(): ?string;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract protected function rows(): array;

    protected function emptyMessage(): string
    {
        return 'Nothing to show right now.';
    }

    protected function actionLabel(): ?string
    {
        return null;
    }

    protected function actionUrl(): ?string
    {
        return null;
    }

    protected function getViewData(): array
    {
        return [
            'heading' => $this->heading(),
            'description' => $this->description(),
            'rows' => $this->rows(),
            'emptyMessage' => $this->emptyMessage(),
            'actionLabel' => $this->actionLabel(),
            'actionUrl' => $this->actionUrl(),
            'widgetKey' => Str::kebab(class_basename(static::class)),
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function queryFor(string $modelClass): Builder
    {
        return AdminAccess::scopeQuery(
            $modelClass::query(),
            Filament::auth()->user(),
            $modelClass,
        );
    }

    protected function canAccessTool(string $toolKey): bool
    {
        return AdminAccess::canAccessToolOrAssignedRecords(Filament::auth()->user(), $toolKey);
    }

    protected function editUrl(string $resourceClass, Model $record): ?string
    {
        try {
            return $resourceClass::getUrl('edit', ['record' => $record]);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resourceUrl(string $resourceClass): ?string
    {
        try {
            return $resourceClass::getUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function row(
        string $type,
        string $title,
        ?string $meta = null,
        ?string $url = null,
        string $status = 'Open',
        string $statusColor = 'gray',
        ?string $imageUrl = null,
    ): array {
        return compact('type', 'title', 'meta', 'url', 'status', 'statusColor', 'imageUrl');
    }

    protected function formatDate(Carbon|string|null $date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $date->toFormattedDateString();
    }
}
