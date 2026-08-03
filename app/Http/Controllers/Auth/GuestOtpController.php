<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GuestOtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService,
    ) {
        //
    }

    /**
     * Show the guest login form.
     */
    public function create()
    {
        return Inertia::render('Guest/Login');
    }

    /**
     * Send an OTP to the guest's email.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = $request->input('email');

        // Verify that the email exists as a guest in at least one property.
        $guest = Guest::where('email', $email)->first();

        if (! $guest) {
            return back()->withErrors(['email' => 'No guest record found with this email.']);
        }

        $this->otpService->send($email, 'guest_login', ['guest_id' => $guest->id]);

        return redirect()->route('guest.otp.verify.form', ['email' => $email])
            ->with('success', 'A verification code has been sent to your email.');
    }

    /**
     * Show the OTP verification form.
     */
    public function verifyForm(Request $request)
    {
        return Inertia::render('Guest/VerifyOtp', [
            'email' => $request->input('email'),
        ]);
    }

    /**
     * Verify the OTP and log the guest in.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'code' => 'required|string|size:6',
        ]);

        $otp = $this->otpService->verify($request->input('email'), $request->input('code'), 'guest_login');

        if (! $otp) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $guest = Guest::where('email', $request->input('email'))->first();

        if (! $guest) {
            return back()->withErrors(['email' => 'Guest record not found.']);
        }

        // Create or update a guest user account linked to this guest record.
        $user = User::firstOrCreate(
            ['email' => $request->input('email')],
            [
                'username' => $request->input('email'),
                'first_name' => $guest->first_name,
                'last_name' => $guest->last_name,
                'password' => Hash::make(Str::random(64)),
                'role' => 'guest',
                'email_verified_at' => now(),
            ]
        );

        if (! $user->isGuest()) {
            $user->update(['role' => 'guest']);
        }

        // Link guest CRM record to user if not already linked.
        if (! $guest->user_id) {
            $guest->update(['user_id' => $user->id]);
        }

        Auth::login($user);

        return redirect()->route('guest.portal')->with('success', 'Welcome back.');
    }

    /**
     * Log the guest out.
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guest.login');
    }
}
