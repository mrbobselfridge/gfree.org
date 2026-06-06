<?php

namespace App\Filament\Admin\Resources\FileDocuments\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListFileDocuments extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = FileDocumentResource::class;
}
