<?php

namespace App\Filament\Admin\Support;

use App\Models\WorkflowNotificationRule;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class WorkflowNotificationActions
{
    public static function notifyTeamForRecord(Model $record, bool $withShortcut = true, string $name = 'notifyTeam'): ?Action
    {
        $service = app(WorkflowNotificationService::class);
        $options = $service->manualRuleOptionsForRecord($record);

        if (! $options) {
            return null;
        }

        $action = Action::make($name)
            ->label('Notify')
            ->color('gray')
            ->modalHeading('Notify team')
            ->modalSubmitActionLabel('Send notification')
            ->fillForm([
                'rule_ids' => array_keys($options),
            ])
            ->schema([
                CheckboxList::make('rule_ids')
                    ->label('Workflow messages')
                    ->options($options)
                    ->required()
                    ->columns(1),
                Textarea::make('manual_recipient_emails')
                    ->label('Additional recipient emails')
                    ->helperText('Separate addresses with commas, semicolons, or new lines.')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('manual_message')
                    ->label('Message')
                    ->helperText('Optional note shown near the top of this manual notification email.')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) use ($record, $service): void {
                $count = $service->manualForRecord(
                    record: $record,
                    ruleIds: $data['rule_ids'] ?? [],
                    manualRecipientEmails: $data['manual_recipient_emails'] ?? null,
                    manualMessage: $data['manual_message'] ?? null,
                );

                Notification::make()
                    ->title($count === 1 ? 'Workflow notification sent' : "{$count} workflow notifications sent")
                    ->success()
                    ->send();
            });

        if ($withShortcut) {
            $action->keyBindings(['alt+b']);
        }

        return IconOnlyAction::make(
            $action,
            Heroicon::OutlinedBell,
            $withShortcut ? 'Notify (Alt+B)' : 'Notify',
        );
    }

    public static function notifyTeamForRecordActions(Model $record, bool $withShortcut = true, string $name = 'notifyTeam'): array
    {
        $action = self::notifyTeamForRecord($record, $withShortcut, $name);

        return $action ? [$action] : [];
    }

    public static function notifyTeamForArea(
        string $area,
        string $recordKey,
        string $recordLabel,
        ?string $adminUrl = null,
        ?string $publicUrl = null,
        bool $withShortcut = true,
        string $name = 'notifyTeam',
    ): ?Action {
        $service = app(WorkflowNotificationService::class);
        $options = $service->rulesFor($area, WorkflowNotificationRule::TRIGGER_MANUAL)
            ->pluck('name', 'id')
            ->all();

        if (! $options) {
            return null;
        }

        $action = Action::make($name)
            ->label('Notify')
            ->color('gray')
            ->modalHeading('Notify team')
            ->modalSubmitActionLabel('Send notification')
            ->fillForm([
                'rule_ids' => array_keys($options),
            ])
            ->schema([
                CheckboxList::make('rule_ids')
                    ->label('Workflow messages')
                    ->options($options)
                    ->required()
                    ->columns(1),
                Textarea::make('manual_recipient_emails')
                    ->label('Additional recipient emails')
                    ->helperText('Separate addresses with commas, semicolons, or new lines.')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('manual_message')
                    ->label('Message')
                    ->helperText('Optional note shown near the top of this manual notification email.')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) use ($area, $recordKey, $recordLabel, $adminUrl, $publicUrl, $service): void {
                $count = $service->manual(
                    area: $area,
                    ruleIds: $data['rule_ids'] ?? [],
                    recordKey: $recordKey,
                    recordLabel: $recordLabel,
                    adminUrl: $adminUrl,
                    publicUrl: $publicUrl,
                    manualRecipientEmails: $data['manual_recipient_emails'] ?? null,
                    manualMessage: $data['manual_message'] ?? null,
                );

                Notification::make()
                    ->title($count === 1 ? 'Workflow notification sent' : "{$count} workflow notifications sent")
                    ->success()
                    ->send();
            });

        if ($withShortcut) {
            $action->keyBindings(['alt+b']);
        }

        return IconOnlyAction::make(
            $action,
            Heroicon::OutlinedBell,
            $withShortcut ? 'Notify (Alt+B)' : 'Notify',
        );
    }

    public static function notifyTeamForAreaActions(
        string $area,
        string $recordKey,
        string $recordLabel,
        ?string $adminUrl = null,
        ?string $publicUrl = null,
        bool $withShortcut = true,
        string $name = 'notifyTeam',
    ): array {
        $action = self::notifyTeamForArea($area, $recordKey, $recordLabel, $adminUrl, $publicUrl, $withShortcut, $name);

        return $action ? [$action] : [];
    }
}
