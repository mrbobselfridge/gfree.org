<?php

namespace App\Filament\Admin\Resources\FileCategories\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\FileCategories\Pages\Concerns\HasFileLibraryBreadcrumbs;
use App\Filament\Admin\Resources\FileCategories\FileCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditFileCategory extends EditRecord
{
    use HasFileLibraryBreadcrumbs;
    use UsesStandardEditActions;

    protected static string $resource = FileCategoryResource::class;
}
