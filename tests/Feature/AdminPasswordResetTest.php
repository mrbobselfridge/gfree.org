<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminResetPassword;
use Filament\Facades\Filament;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_links_to_password_reset_request(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee('/admin/password-reset/request', false);
    }

    public function test_admin_password_reset_request_emails_a_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', $user->email)
            ->call('request')
            ->assertHasNoErrors();

        Notification::assertSentTo(
            $user,
            AdminResetPassword::class,
            fn (AdminResetPassword $notification): bool => str_contains($notification->url, '/admin/password-reset/reset')
                && str_contains($notification->url, 'email=admin%40example.com'),
        );
    }

    public function test_admin_password_reset_email_is_not_queued(): void
    {
        $notification = new AdminResetPassword('token');

        $this->assertNotInstanceOf(ShouldQueue::class, $notification);
    }

    public function test_admin_password_reset_link_can_update_the_users_password(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
            'password' => 'old-password',
        ]);

        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, [
            'email' => $user->email,
            'token' => $token,
        ])
            ->set('password', 'new-password')
            ->set('passwordConfirmation', 'new-password')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_user_menu_includes_change_password_link(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]));

        Filament::setCurrentPanel('admin');

        $items = Filament::getUserMenuItems();

        $this->assertArrayHasKey('changePassword', $items);
        $this->assertSame('Change Password', $items['changePassword']->getLabel());
        $this->assertSame(route('admin.change-password'), $items['changePassword']->getUrl());
    }

    public function test_change_password_link_redirects_to_current_user_password_reset(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
            'password' => 'old-password',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.change-password'));

        $response
            ->assertRedirect()
            ->assertRedirectContains('/admin/password-reset/reset')
            ->assertRedirectContains('email=admin%40example.com');

        $redirectUrl = $response->headers->get('Location');
        parse_str((string) parse_url((string) $redirectUrl, PHP_URL_QUERY), $query);

        $this->assertGuest();

        Livewire::test(ResetPassword::class, [
            'email' => $query['email'],
            'token' => $query['token'],
        ])
            ->set('password', 'new-password')
            ->set('passwordConfirmation', 'new-password')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }
}
