<?php

namespace App\Filament\Admin\Resources\StaffMembers\Pages;

use App\Filament\Admin\Resources\Concerns\RedirectsCreateToIndex;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffMember extends CreateRecord
{
    use RedirectsCreateToIndex;

    protected static string $resource = StaffMemberResource::class;
}
