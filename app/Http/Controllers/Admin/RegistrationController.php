<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WaitlistWelcomeMail;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    public function index()
    {
        $registrations = Registration::latest()->paginate(15);

        return Inertia::render('Admin/Registrations/Index', [
            'registrations' => $registrations,
        ]);
    }

    public function update(Request $request, Registration $registration)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,converted',
        ]);

        $previousStatus = $registration->status;

        $registration->update($validated);

        // Fire the "Welcome to the Tena Family" mail only when the admin
        // transitions the signup into "converted" from something else —
        // never on the initial signup, and never on repeat updates that
        // leave the status where it was.
        if ($validated['status'] === 'converted' && $previousStatus !== 'converted') {
            try {
                Mail::to($registration->email)->send(
                    new WaitlistWelcomeMail(
                        firstName: $registration->first_name,
                        lastName: $registration->last_name,
                        email: $registration->email,
                        actionUrl: route('register'),
                    )
                );
                Log::info("Waitlist welcome email sent to {$registration->email} on conversion");
            } catch (\Throwable $e) {
                Log::error('Failed to send waitlist welcome email: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Registration updated successfully.');
    }

    public function destroy(Registration $registration)
    {
        $registration->delete();

        return redirect()->back()->with('success', 'Registration deleted successfully.');
    }
}
