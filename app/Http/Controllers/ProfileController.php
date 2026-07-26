<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        $twoFactorSecret = null;
        $twoFactorQrCodeUrl = null;
        $twoFactorRecoveryCodes = null;

        if ($user->two_factor_enabled && ! $user->two_factor_confirmed_at) {
            $google2fa = new Google2FA;
            $twoFactorSecret = $user->two_factor_secret ?? $google2fa->generateSecretKey();
            $twoFactorQrCodeUrl = $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $twoFactorSecret
            );
        }

        if ($user->two_factor_enabled && $user->two_factor_confirmed_at) {
            $twoFactorRecoveryCodes = $user->two_factor_recovery_codes;
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'twoFactorEnabled' => (bool) $user->two_factor_enabled,
            'twoFactorConfirmed' => (bool) $user->two_factor_confirmed_at,
            'twoFactorSecret' => $twoFactorSecret,
            'twoFactorQrCodeUrl' => $twoFactorQrCodeUrl,
            'twoFactorRecoveryCodes' => $twoFactorRecoveryCodes,
            'userEmail' => $user->email,
        ]);
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'secret' => ['required', 'string'],
        ]);

        $user = $request->user();
        $google2fa = new Google2FA;

        $valid = $google2fa->verifyKey($request->secret, $request->code, 1);

        if (! $valid) {
            return back()->withErrors(['code' => 'The verification code is invalid.'])->withInput();
        }

        $recoveryCodes = [];
        for ($i = 0; $i < 10; $i++) {
            $recoveryCodes[] = strtoupper(bin2hex(random_bytes(5)));
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $request->secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        return redirect()->route('profile.edit')->with('status', 'Two-Factor Authentication has been enabled.');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return redirect()->route('profile.edit')->with('status', 'Two-Factor Authentication has been disabled.');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
