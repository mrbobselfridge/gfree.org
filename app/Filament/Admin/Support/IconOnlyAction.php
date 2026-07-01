<?php

namespace App\Filament\Admin\Support;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;

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
            ->tooltip($tooltip ?? self::tooltipForAction($action))
            ->iconButton();
    }

    private static function tooltipForAction(Action $action): string|Htmlable|null
    {
        $label = $action->getLabel();

        if (! is_string($label)) {
            return $label;
        }

        $shortcuts = collect(Arr::wrap($action->getKeyBindings()))
            ->map(fn (string $binding): ?string => self::shortcutLabel($binding))
            ->filter()
            ->unique()
            ->values();

        if ($shortcuts->isEmpty()) {
            return $label;
        }

        return $label.' ('.$shortcuts->implode(', ').')';
    }

    private static function shortcutLabel(string $binding): ?string
    {
        return match (strtolower($binding)) {
            'mod+s' => 'Ctrl/Cmd+S',
            'mod+enter', 'ctrl+enter' => 'Ctrl/Cmd+Enter',
            'mod+shift+s' => 'Ctrl/Cmd+Shift+S',
            'mod+d' => 'Ctrl/Cmd+D',
            'alt+a' => 'Alt+A',
            'alt+c' => 'Alt+C',
            'alt+d' => 'Alt+D',
            'alt+e' => 'Alt+E',
            'alt+n' => 'Alt+N',
            'alt+plus' => 'Alt++',
            'alt+v' => 'Alt+V',
            default => null,
        };
    }
}
