<?php

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\Pages\PageResource;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = PageResource::class;
}
