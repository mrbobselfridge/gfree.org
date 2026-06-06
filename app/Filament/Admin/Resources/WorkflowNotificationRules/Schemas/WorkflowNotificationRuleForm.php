<?php

namespace App\Filament\Admin\Resources\WorkflowNotificationRules\Schemas;

use App\Models\User;
use App\Support\WorkflowNotificationAreas;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkflowNotificationRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rule')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        ToggleButtons::make('is_enabled')
                            ->boolean()
                            ->inline()
                            ->default(false)
                            ->required(),

                        Select::make('content_area')
                            ->label('Content area')
                            ->options(WorkflowNotificationAreas::options())
                            ->native(false)
                            ->required(),

                        CheckboxList::make('triggers')
                            ->options(WorkflowNotificationAreas::triggerOptions())
                            ->columns(4)
                            ->bulkToggleable()
                            ->required(),
                        Select::make('delay_minutes')
                            ->label('Automatic send delay')
                            ->options(WorkflowNotificationAreas::delayOptions())
                            ->default(15)
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Recipients')
                    ->description('Choose users, groups, external emails, or any combination.')
                    ->schema([
                        Toggle::make('notify_admins')
                            ->label('All admins'),
                        Toggle::make('notify_all_users')
                            ->label('All admins and users')
                            ->helperText('When enabled, every user account receives this notification.'),
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
                                ->all()),
                        Textarea::make('extra_emails')
                            ->label('Extra email addresses')
                            ->helperText('Separate addresses with commas, semicolons, or new lines.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Email')
                    ->schema([
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('message')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
