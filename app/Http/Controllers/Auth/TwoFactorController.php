<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/TwoFactorChallenge', [
            'email' => session('2fa_user_email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('2fa_user_id');

        if (! $userId) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'User not found.']);
        }

        $google2fa = new Google2FA;
        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->input('code'), 1);

        if (! $valid) {
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        Auth::login($user, session('2fa_remember', false));

        $request->session()->forget(['2fa_user_id', '2fa_user_email', '2fa_remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
