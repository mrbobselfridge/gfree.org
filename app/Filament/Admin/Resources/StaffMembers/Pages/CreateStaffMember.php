<?php

namespace App\Filament\Admin\Resources\StaffMembers\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffMember extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = StaffMemberResource::class;
}
