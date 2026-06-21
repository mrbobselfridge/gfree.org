<?php

namespace App\Filament\Admin\Resources\WorkflowNotificationRules\Schemas;

use App\Models\User;
use App\Support\WorkflowNotificationAreas;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WorkflowNotificationRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Rule')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('content_area')
                            ->label('Content area')
                            ->options(WorkflowNotificationAreas::options())
                            ->native(false)
                            ->required(),


                        ToggleButtons::make('is_enabled')
                            ->inline()
                            ->boolean()
                            ->default(false)
                            ->required(),

                        CheckboxList::make('triggers')
                            ->label('Available triggers')
                            ->options(WorkflowNotificationAreas::triggerOptions())
                            ->columns(2)
                            ->required(),

                        Select::make('delay_minutes')
                            ->label('Automatic send delay')
                            ->options(WorkflowNotificationAreas::delayOptions())
                            ->default(15)
                            ->native(false)
                            ->required(),

                        Placeholder::make('delay_minutes_help')
                            ->label('What is "Automatic send delay"?')
                            ->content(new HtmlString(
                                '<div style="font-size: 0.75rem; line-height: 1.35;">
                                    Sets how long the system waits before sending a notification after a change. If more updates happen during that time, the delay restarts to avoid sending multiple emails.
                                </div>'
                            ))
                            ->columnSpan(1),


                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Recipients')
                    ->description('Choose users, groups, external emails, or any combination.')
                    ->schema([
                        Toggle::make('notify_admins')
                            ->label('All admins')
                            ->helperText('When enabled, every ADMIN account receives this notification.'),
                        Toggle::make('notify_all_users')
                            ->label('All admins and users')
                            ->helperText('When enabled, every ADMIN and USER account receives this notification.'),
                        Select::make('selected_user_ids')
                            ->label('Selected users')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => User::query()
                                ->orderBy('name')
                                ->get(['id', 'name', 'email'])
                                ->mapWithKeys(fn (User $user): array => [
                                    (string) $user->getKey() => trim("{$user->name} <{$user->email}>"),
                                ])
                                ->all())
                                ->columnSpan(2),
                        Textarea::make('extra_emails')
                            ->label('Extra email addresses')
                            ->helperText('Separate addresses with commas, semicolons, or new lines.')
                            ->rows(2)
                                ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Email')
                    ->description('Supports template items such as {church_name}, {site_name}, {current_date}, {current_time}, {current_datetime}, {page_title}, {action_status}, {updater_name}, and {updater_email}.')
                    ->schema([
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('message')
                            ->required()
                            ->rows(6),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }
}
