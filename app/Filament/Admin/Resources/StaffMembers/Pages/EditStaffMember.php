<?php

namespace App\Filament\Admin\Resources\StaffMembers\Pages;

use App\Filament\Admin\Resources\Concerns\RedirectsEditToIndex;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffMember extends EditRecord
{
    use RedirectsEditToIndex;

    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
