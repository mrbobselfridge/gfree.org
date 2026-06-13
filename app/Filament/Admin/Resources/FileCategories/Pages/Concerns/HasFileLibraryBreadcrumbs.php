<?php

namespace App\Filament\Admin\Resources\FileCategories\Pages\Concerns;

use App\Filament\Admin\Resources\FileCategories\FileCategoryResource;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;

trait HasFileLibraryBreadcrumbs
{
    public function getResourceBreadcrumbs(): array
    {
        return [
            FileDocumentResource::getUrl() => FileDocumentResource::getBreadcrumb(),
            FileCategoryResource::getUrl() => FileCategoryResource::getBreadcrumb(),
        ];
    }
}
