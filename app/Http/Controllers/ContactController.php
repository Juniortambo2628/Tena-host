<?php

namespace App\Http\Controllers;

use App\Mail\ContactEnquiryMail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $key = 'contact:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        RateLimiter::hit($key, 300);

        $recipient = Setting::getValue('support_email', config('mail.from.address', 'info@tena.host'));

        try {
            Mail::to($recipient)->send(
                new ContactEnquiryMail(
                    senderName: $validated['name'],
                    senderEmail: $validated['email'],
                    subjectLine: $validated['subject'],
                    messageBody: $validated['message'],
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send contact enquiry email: '.$e->getMessage());

            return response()->json([
                'message' => 'We could not send your message right now. Please try again later.',
            ], 500);
        }

        return response()->json([
            'message' => "Thanks — we'll get back to you shortly.",
        ], 201);
    }
}
