<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Mail\PasswordChangedMail;
use App\Mail\WelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationTestController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'template' => 'required|string|in:welcome,password_changed,otp',
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
        }

        return back()->with('success', "Test email ({$validated['template']}) sent to {$email}");
    }
}
