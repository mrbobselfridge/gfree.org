<?php

namespace App\Filament\Admin\Resources\StaffMembers\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use Filament\Resources\Pages\ListRecords;

class ListStaffMembers extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = StaffMemberResource::class;
}
