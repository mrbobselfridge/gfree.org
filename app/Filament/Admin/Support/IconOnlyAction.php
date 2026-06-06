<?php

namespace App\Filament\Admin\Support;

use BackedEnum;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;

class IconOnlyAction
{
    public static function make(Action $action, BackedEnum|string|null $icon = null, string|Htmlable|null $tooltip = null): Action
    {
        if ($icon) {
            $action->icon($icon);
        }

        return $action
            ->tooltip($tooltip ?? $action->getLabel())
            ->iconButton();
    }
}
