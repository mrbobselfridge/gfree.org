<?php

namespace App\Filament\Admin\Resources\FileCategories\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\FileCategories\Pages\Concerns\HasFileLibraryBreadcrumbs;
use App\Filament\Admin\Resources\FileCategories\FileCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListFileCategories extends ListRecords
{
    use HasFileLibraryBreadcrumbs;
    use UsesStandardListActions;

    protected static string $resource = FileCategoryResource::class;
}
