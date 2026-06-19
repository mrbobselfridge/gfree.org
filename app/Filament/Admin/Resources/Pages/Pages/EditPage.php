<?php

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Support\CodeBlockAccess;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! CodeBlockAccess::canManage()) {
            $record = $this->getRecord();

            $data['seo_title'] = $record->seo_title;
            $data['seo_description'] = $record->seo_description;
            $data['noindex_nofollow'] = $record->noindex_nofollow;
        }

        return $data;
    }
}
