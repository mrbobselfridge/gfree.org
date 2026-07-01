<?php

namespace App\Filament\Admin\Support;

use App\Support\PublicPageUrls;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class PublicPageActions
{
    public static function tableAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('viewPublicPage')
                ->label('View')
                ->color('gray')
                ->url(fn (Model $record): ?string => PublicPageUrls::forRecord($record), true)
                ->hidden(fn (Model $record): bool => blank(PublicPageUrls::forRecord($record))),
            Heroicon::OutlinedArrowTopRightOnSquare,
        );
    }

    public static function button(string $name, ?string $url, string $label = 'View', bool $withShortcut = true): ?Action
    {
        if (blank($url)) {
            return null;
        }

        $action = Action::make($name)
            ->label($label)
            ->url($url, true)
            ->color('gray');

        if ($withShortcut) {
            $action->keyBindings(['alt+v']);
        }

        return IconOnlyAction::make(
            $action,
            Heroicon::OutlinedArrowTopRightOnSquare,
            $withShortcut ? "{$label} (Alt+V)" : $label,
        );
    }

    public static function notificationAction(?string $url): ?Action
    {
        if (blank($url)) {
            return null;
        }

        return Action::make('viewPublicPage')
            ->label('View')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->url($url, true);
    }

    public static function withNotificationAction(Notification $notification, ?string $url): Notification
    {
        $action = self::notificationAction($url);

        if (! $action) {
            return $notification;
        }

        return $notification
            ->duration(10000)
            ->actions([$action]);
    }
}
