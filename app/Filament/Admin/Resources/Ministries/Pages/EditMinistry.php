<?php

namespace App\Filament\Admin\Resources\Ministries\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\Ministries\MinistryResource;
use Filament\Resources\Pages\EditRecord;

class EditMinistry extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = MinistryResource::class;
}
