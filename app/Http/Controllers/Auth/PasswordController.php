<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $user = $request->user();

        Mail::to($user->email)->send(new PasswordChangedMail(
            name: $user->first_name ?: $user->email,
            changedAt: now()->format('M d, Y \a\t g:i A'),
            ipAddress: $request->ip(),
            device: $request->userAgent(),
        ));

        return back();
    }
}
