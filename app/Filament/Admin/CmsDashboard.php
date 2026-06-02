<?php

namespace App\Filament\Admin;

use Filament\Pages\Dashboard;

class CmsDashboard extends Dashboard
{
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }
}
