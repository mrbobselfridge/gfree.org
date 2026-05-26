<?php

namespace App\Filament\Admin\Resources\Ministries\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\Ministries\MinistryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMinistry extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = MinistryResource::class;
}
