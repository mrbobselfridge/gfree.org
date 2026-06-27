<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

class ChangePasswordController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user instanceof User, Response::HTTP_FORBIDDEN);

        $resetPasswordUrl = Filament::getResetPasswordUrl(
            Password::broker()->createToken($user),
            $user,
        );

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->to($resetPasswordUrl);
    }
}
