<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\User;

class UserAccountNotificationTemplate
{
    /**
     * @return array<string, string>
     */
    public static function variables(User $recipient, ?User $actor, string $resetPasswordUrl): array
    {
        $now = now();
        $siteName = SiteSetting::query()->value('church_name') ?: config('app.name', 'TwyxtCo');

        return [
            'site_name' => $siteName,
            'current_date' => $now->format('M j, Y'),
            'current_time' => $now->format('g:i A'),
            'current_datetime' => $now->format('M j, Y g:i A'),
            'page_title' => (string) $recipient->name,
            'action_status' => 'Account Notification',
            'updater_name' => (string) ($actor?->name ?? ''),
            'updater_email' => (string) ($actor?->email ?? ''),
            'user_name' => (string) $recipient->name,
            'user_email' => (string) $recipient->email,
            'user_access' => AdminAccess::accessSummary($recipient),
            'admin_url' => url('/admin'),
            'admin_manual_url' => route('manual'),
            'reset_password_url' => $resetPasswordUrl,
        ];
    }

    public static function render(?string $template, User $recipient, ?User $actor, string $resetPasswordUrl): string
    {
        $template = (string) $template;

        if ($template === '') {
            return '';
        }

        $rendered = strtr(
            $template,
            collect(self::variables($recipient, $actor, $resetPasswordUrl))
                ->mapWithKeys(fn (string $value, string $key): array => ['{'.$key.'}' => $value])
                ->all(),
        );

        return SiteVariables::renderHtml($rendered);
    }

    public static function renderSubject(?string $template, User $recipient, ?User $actor, string $resetPasswordUrl): string
    {
        return str(strip_tags(html_entity_decode(self::render($template, $recipient, $actor, $resetPasswordUrl))))
            ->squish()
            ->limit(255, '')
            ->toString();
    }

    public static function supportedTokenHelp(): string
    {
        return 'Supports {site_name}, {current_date}, {current_time}, {current_datetime}, {page_title}, {action_status}, {updater_name}, {updater_email}, {user_name}, {user_email}, {user_access}, {admin_url}, {admin_manual_url}, {reset_password_url}, and site variables like [[variable-name]].';
    }
}
