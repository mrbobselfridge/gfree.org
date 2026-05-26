<?php

namespace App\Filament\Admin\Resources\StaffMembers\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use Filament\Resources\Pages\EditRecord;

class EditStaffMember extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = StaffMemberResource::class;
}
