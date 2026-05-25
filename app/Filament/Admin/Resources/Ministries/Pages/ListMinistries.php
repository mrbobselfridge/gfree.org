<?php

namespace App\Filament\Admin\Resources\Ministries\Pages;

use App\Filament\Admin\Resources\Ministries\MinistryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMinistries extends ListRecords
{
    protected static string $resource = MinistryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
