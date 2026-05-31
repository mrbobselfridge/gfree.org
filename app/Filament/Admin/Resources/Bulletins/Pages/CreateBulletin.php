<?php

namespace App\Filament\Admin\Resources\Bulletins\Pages;

use App\Filament\Admin\Resources\Bulletins\BulletinResource;
use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use Filament\Resources\Pages\CreateRecord;

class CreateBulletin extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = BulletinResource::class;
}
