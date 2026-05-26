<?php

namespace App\Filament\Admin\Resources\Ministries\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\Ministries\MinistryResource;
use Filament\Resources\Pages\ListRecords;

class ListMinistries extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = MinistryResource::class;
}
