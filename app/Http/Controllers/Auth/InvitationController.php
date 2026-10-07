<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

/**
 * Accept an invite (signed, expiring link from SignupConversionService):
 * set a password and sign straight in. Works for phone-only accounts, which
 * Laravel's email-based password reset can't serve.
 */
class InvitationController extends Controller
{
    public function show(Request $request, User $user)
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $user->email ?: $user->phone_number,
            'invitation' => [
                'action' => $request->fullUrl(),
                'name' => $user->first_name,
            ],
        ]);
    }

    public function store(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($request->password),
            // The signed link reached them, which verifies the account.
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
