<?php

namespace App\Filament\Admin\Resources\FileCategories\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\FileCategories\Pages\Concerns\HasFileLibraryBreadcrumbs;
use App\Filament\Admin\Resources\FileCategories\FileCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFileCategory extends CreateRecord
{
    use HasFileLibraryBreadcrumbs;
    use UsesStandardCreateActions;

    protected static string $resource = FileCategoryResource::class;
}
