<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Mail\UserAccountNotificationMail;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class UserAccountNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_edit_screen_has_notify_action(): void
    {
        $target = User::factory()->create([
            'name' => 'Content Editor',
            'email' => 'editor@example.com',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditUser::class, ['record' => $target->getKey()])
            ->assertActionExists('notifyUserAccount')
            ->assertActionHasLabel('notifyUserAccount', 'Notify');
    }

    public function test_notify_action_sends_custom_account_email_with_supported_variables(): void
    {
        Mail::fake();

        SiteSetting::query()->create([
            'church_name' => 'Grace Free Church',
            'site_variables' => [
                [
                    'name' => 'Short Name',
                    'variable' => 'short-name',
                    'value' => 'GFC',
                ],
            ],
        ]);

        $actor = User::factory()->create([
            'name' => 'Admin Sender',
            'email' => 'sender@example.com',
        ]);

        $target = User::factory()->create([
            'name' => 'Content Editor',
            'email' => 'notified@example.com',
        ]);

        Livewire::actingAs($actor)
            ->test(EditUser::class, ['record' => $target->getKey()])
            ->callAction('notifyUserAccount', [
                'subject' => 'Welcome {user_name} to [[short-name]]',
                'message' => <<<'TEXT'
Hello {user_name},

You have an account at {site_name}.

Admin URL: {admin_url}
Manual: {admin_manual_url}
Reset: {reset_password_url}
Status: {action_status}
Updated by: {updater_name} <{updater_email}>
Legacy title: {page_title}
Site variable: [[short-name]]
TEXT,
            ])
            ->assertHasNoActionErrors();

        Mail::assertSent(UserAccountNotificationMail::class, function (UserAccountNotificationMail $mail) use ($target): bool {
            $html = $mail->render();

            return $mail->hasTo($target->email)
                && $mail->subjectLine === 'Welcome Content Editor to GFC'
                && str_contains($html, 'Hello Content Editor')
                && str_contains($html, 'You have an account at Grace Free Church.')
                && str_contains($html, url('/admin'))
                && str_contains($html, route('manual'))
                && str_contains($html, '/admin/password-reset/reset')
                && str_contains($html, 'email=notified%40example.com')
                && str_contains($html, 'Status: Account Notification')
                && str_contains($html, 'Updated by: Admin Sender')
                && str_contains($html, 'sender@example.com')
                && str_contains($html, 'Legacy title: Content Editor')
                && str_contains($html, 'Site variable: GFC');
        });
    }

    public function test_notify_action_can_render_user_access_summary(): void
    {
        Mail::fake();

        $visitPage = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
        ]);

        $givingPage = Page::query()->create([
            'title' => 'Giving',
            'slug' => 'giving',
        ]);

        $actor = User::factory()->create([
            'name' => 'Admin Sender',
        ]);

        $target = User::factory()->create([
            'name' => 'Content Editor',
            'email' => 'notified@example.com',
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::HOMEPAGE_CONTENT, AdminAccess::MEDIA_LIBRARY, AdminAccess::SITE_SETTINGS],
                'records' => [
                    AdminAccess::PAGES => [(string) $visitPage->getKey(), (string) $givingPage->getKey()],
                ],
            ],
        ]);

        Livewire::actingAs($actor)
            ->test(EditUser::class, ['record' => $target->getKey()])
            ->callAction('notifyUserAccount', [
                'subject' => 'Access for {user_name}',
                'message' => "Access:\n{user_access}",
            ])
            ->assertHasNoActionErrors();

        Mail::assertSent(UserAccountNotificationMail::class, function (UserAccountNotificationMail $mail) use ($target): bool {
            $html = $mail->render();

            return $mail->hasTo($target->email)
                && str_contains($html, 'Content: Homepage, Media Library')
                && str_contains($html, 'Site Tools: Site Settings')
                && str_contains($html, 'Individual Page Entries: Giving, Visit');
        });
    }

    public function test_notify_action_uses_edited_subject_and_message(): void
    {
        Mail::fake();

        $target = User::factory()->create([
            'name' => 'Site Admin',
            'email' => 'site-admin@example.com',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditUser::class, ['record' => $target->getKey()])
            ->callAction('notifyUserAccount', [
                'subject' => 'Custom account subject for {user_name}',
                'message' => 'Custom editable message for {user_email}.',
            ])
            ->assertHasNoActionErrors();

        Mail::assertSent(UserAccountNotificationMail::class, fn (UserAccountNotificationMail $mail): bool => $mail->hasTo('site-admin@example.com')
            && $mail->subjectLine === 'Custom account subject for Site Admin'
            && str_contains($mail->render(), 'Custom editable message for site-admin@example.com.'));
    }
}
