<?php

namespace App\Filament\Admin\Support;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;

class IconOnlyAction
{
    public static function make(Action $action, BackedEnum|string|null $icon = null, string|Htmlable|null $tooltip = null): Action
    {
        if ($icon) {
            $action->icon($icon);
        }

        return $action
            ->size(Size::ExtraLarge)
            ->extraAttributes(['class' => 'twyxtco-admin-icon-action'], merge: true)
            ->tooltip($tooltip ?? $action->getLabel())
            ->iconButton();
    }
}
