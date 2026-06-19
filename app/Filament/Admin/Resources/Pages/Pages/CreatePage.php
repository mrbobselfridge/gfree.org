<?php

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Support\CodeBlockAccess;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! CodeBlockAccess::canManage()) {
            unset($data['seo_title'], $data['seo_description'], $data['noindex_nofollow']);
        }

        return $data;
    }
}
