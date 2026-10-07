<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WaitlistWelcomeMail;
use App\Models\Registration;
use App\Services\SignupConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $type = in_array($request->query('type'), Registration::TYPES, true) ? $request->query('type') : null;

        $registrations = Registration::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Registrations/Index', [
            'registrations' => $registrations,
            'filters' => ['type' => $type],
            'typeCounts' => Registration::selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
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
        if ($validated['status'] === 'converted' && $previousStatus !== 'converted' && $registration->email) {
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

    /**
     * Create the account for a sign-up (or re-send its invite).
     */
    public function convert(Request $request, Registration $registration, SignupConversionService $conversion)
    {
        $validated = $request->validate([
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,whatsapp',
        ]);

        ['user' => $user, 'sent' => $sent] = $conversion->convert($registration, $validated['channels']);

        return redirect()->back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? "Account ready for {$user->first_name}. Invite sent by ".implode(' and ', array_unique($sent)).'.'
                : "Account ready for {$user->first_name}, but the invite couldn't be sent. Check the messaging settings.",
        );
    }

    public function destroy(Registration $registration)
    {
        $registration->delete();

        return redirect()->back()->with('success', 'Registration deleted successfully.');
    }
}
