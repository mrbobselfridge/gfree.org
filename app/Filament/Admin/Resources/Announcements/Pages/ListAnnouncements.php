<?php

namespace App\Filament\Admin\Resources\Announcements\Pages;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = AnnouncementResource::class;
}
