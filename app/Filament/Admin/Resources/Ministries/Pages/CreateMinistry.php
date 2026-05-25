<?php

namespace App\Filament\Admin\Resources\Ministries\Pages;

use App\Filament\Admin\Resources\Concerns\RedirectsCreateToIndex;
use App\Filament\Admin\Resources\Ministries\MinistryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMinistry extends CreateRecord
{
    use RedirectsCreateToIndex;

    protected static string $resource = MinistryResource::class;
}
