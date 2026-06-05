<?php

namespace App\Filament\Admin\Support;

use App\Models\WorkflowNotificationRule;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class WorkflowNotificationActions
{
    public static function notifyTeamForRecord(Model $record): ?Action
    {
        $service = app(WorkflowNotificationService::class);
        $options = $service->manualRuleOptionsForRecord($record);

        if (! $options) {
            return null;
        }

        return Action::make('notifyTeam')
            ->label('Notify Team')
            ->icon(Heroicon::OutlinedBell)
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
            ])
            ->action(function (array $data) use ($record, $service): void {
                $count = $service->manualForRecord($record, $data['rule_ids'] ?? []);

                Notification::make()
                    ->title($count === 1 ? 'Workflow notification sent' : "{$count} workflow notifications sent")
                    ->success()
                    ->send();
            });
    }

    public static function notifyTeamForRecordActions(Model $record): array
    {
        $action = self::notifyTeamForRecord($record);

        return $action ? [$action] : [];
    }

    public static function notifyTeamForArea(
        string $area,
        string $recordKey,
        string $recordLabel,
        ?string $adminUrl = null,
        ?string $publicUrl = null,
    ): ?Action {
        $service = app(WorkflowNotificationService::class);
        $options = $service->rulesFor($area, WorkflowNotificationRule::TRIGGER_MANUAL)
            ->pluck('name', 'id')
            ->all();

        if (! $options) {
            return null;
        }

        return Action::make('notifyTeam')
            ->label('Notify Team')
            ->icon(Heroicon::OutlinedBell)
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
            ])
            ->action(function (array $data) use ($area, $recordKey, $recordLabel, $adminUrl, $publicUrl, $service): void {
                $count = $service->manual(
                    area: $area,
                    ruleIds: $data['rule_ids'] ?? [],
                    recordKey: $recordKey,
                    recordLabel: $recordLabel,
                    adminUrl: $adminUrl,
                    publicUrl: $publicUrl,
                );

                Notification::make()
                    ->title($count === 1 ? 'Workflow notification sent' : "{$count} workflow notifications sent")
                    ->success()
                    ->send();
            });
    }

    public static function notifyTeamForAreaActions(
        string $area,
        string $recordKey,
        string $recordLabel,
        ?string $adminUrl = null,
        ?string $publicUrl = null,
    ): array {
        $action = self::notifyTeamForArea($area, $recordKey, $recordLabel, $adminUrl, $publicUrl);

        return $action ? [$action] : [];
    }
}
