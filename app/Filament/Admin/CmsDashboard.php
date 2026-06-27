<?php

namespace App\Filament\Admin;

use Filament\Pages\Dashboard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;

class CmsDashboard extends Dashboard
{
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    public function getPageClasses(): array
    {
        return [
            'twyxtco-cms-dashboard',
        ];
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets()))
            ->extraAttributes(['class' => 'twyxtco-cms-dashboard-widgets'], merge: true);
    }
}
