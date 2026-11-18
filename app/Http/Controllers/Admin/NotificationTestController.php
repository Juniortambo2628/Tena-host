<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Mail\PasswordChangedMail;
use App\Mail\SignupConfirmationMail;
use App\Mail\TestPaymentReceiptMail;
use App\Mail\WaitlistWelcomeMail;
use App\Mail\WelcomeMail;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationTestController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'template' => 'required|string|in:welcome,password_changed,otp,receipt,waitlist_confirmation,waitlist_welcome',
        ]);

        $email = $validated['email'];

        switch ($validated['template']) {
            case 'welcome':
                Mail::to($email)->send(new WelcomeMail(
                    name: 'Test User',
                    actionUrl: route('login'),
                ));
                break;

            case 'password_changed':
                Mail::to($email)->send(new PasswordChangedMail(
                    name: 'Test User',
                    changedAt: now()->format('M d, Y \a\t g:i A'),
                    ipAddress: '127.0.0.1',
                    device: 'Test Browser',
                ));
                break;

            case 'otp':
                Mail::to($email)->send(new OtpMail(code: '123456'));
                break;

            case 'receipt':
                Mail::to($email)->send(new TestPaymentReceiptMail(
                    recipientName: 'Test User',
                    amount: 6500,
                ));
                break;

            case 'waitlist_confirmation':
                // What a website sign-up gets now: "Welcome to TenaFi." (an unsaved sample sign-up).
                Mail::to($email)->send(new SignupConfirmationMail(new Registration([
                    'type' => 'hosts', 'first_name' => 'Test', 'last_name' => 'User', 'email' => $email,
                ])));
                break;

            case 'waitlist_welcome':
                Mail::to($email)->send(new WaitlistWelcomeMail(
                    firstName: 'Test',
                    lastName: 'User',
                    email: $email,
                ));
                break;
        }

        return back()->with('success', "Test email ({$validated['template']}) sent to {$email}");
    }
}
