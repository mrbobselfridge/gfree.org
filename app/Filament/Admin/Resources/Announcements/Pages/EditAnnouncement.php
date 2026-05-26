<?php

namespace App\Filament\Admin\Resources\Announcements\Pages;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = AnnouncementResource::class;
}
