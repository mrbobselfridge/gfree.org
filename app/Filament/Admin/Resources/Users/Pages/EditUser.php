<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Mail\UserAccountNotificationMail;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\UserAccountNotificationTemplate;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class EditUser extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHeaderCancelAction(),
            $this->getHeaderDeleteAction(),
            $this->getHeaderNotifyUserAccountAction(),
            $this->getHeaderSaveAndCloseAction(),
            $this->getHeaderSaveAction(),
        ];
    }

    protected function getHeaderNotifyUserAccountAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('notifyUserAccount')
                ->label('Notify')
                ->color('gray')
                ->modalHeading('Notify user')
                ->modalSubmitActionLabel('Send email')
                ->fillForm(fn(): array => [
                    'recipient_email' => $this->getRecord()->email,
                    'subject' => $this->defaultNotificationSubject(),
                    'message' => $this->defaultNotificationMessage(),
                ])
                ->schema([
                    TextInput::make('recipient_email')
                        ->label('Recipient')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->helperText(UserAccountNotificationTemplate::supportedTokenHelp()),
                    Textarea::make('message')
                        ->required()
                        ->rows(10)
                        ->columnSpanFull()
                        ->helperText(UserAccountNotificationTemplate::supportedTokenHelp()),
                ])
                ->action(function (array $data): void {
                    /** @var User $record */
                    $record = $this->getRecord();
                    $actor = auth()->user() instanceof User ? auth()->user() : null;
                    $resetPasswordUrl = Filament::getResetPasswordUrl(
                        Password::broker()->createToken($record),
                        $record,
                    );

                    Mail::to($record->email)->send(new UserAccountNotificationMail(
                        subjectLine: UserAccountNotificationTemplate::renderSubject($data['subject'] ?? '', $record, $actor, $resetPasswordUrl),
                        bodyText: UserAccountNotificationTemplate::render($data['message'] ?? '', $record, $actor, $resetPasswordUrl),
                    ));

                    Notification::make()
                        ->title('User notification sent')
                        ->success()
                        ->send();
                }),
            Heroicon::OutlinedBell,
        );
    }

    private function defaultNotificationSubject(): string
    {
        return 'Your {church_name} admin account';
    }

    private function defaultNotificationMessage(): string
    {
        return <<<'TEXT'
Hello {user_name},

You have an account in the {church_name} admin system.

Admin URL:
{admin_url}

Use this link to set or reset your password:
{reset_password_url}

You can review the admin user manual here:
{admin_manual_url}
TEXT;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tools = $data['admin_permissions']['tools'] ?? [];

        $data['admin_permissions']['tool_groups'] = [
            'content' => array_values(array_intersect($tools, array_keys(AdminAccess::toolOptionsForGroup('Content')))),
            'sitewide' => array_values(array_intersect($tools, array_keys(AdminAccess::toolOptionsForGroup('Site Tools')))),
            'additional' => array_values(array_intersect($tools, array_keys(AdminAccess::additionalToolOptions()))),
        ];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizePermissions($data);
    }

    private function normalizePermissions(array $data): array
    {
        if (($data['role'] ?? null) === User::ROLE_ADMIN) {
            $data['admin_permissions'] = null;

            return $data;
        }

        $data['admin_permissions'] = [
            'tools' => $this->selectedTools($data),
            'records' => [
                'pages' => array_values($data['admin_permissions']['records']['pages'] ?? []),
            ],
        ];

        return $data;
    }

    private function selectedTools(array $data): array
    {
        $permissions = $data['admin_permissions'] ?? [];

        return collect($permissions['tool_groups'] ?? [])
            ->flatMap(fn(array $tools): array => $tools)
            ->merge($permissions['tools'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
