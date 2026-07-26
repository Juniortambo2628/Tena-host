<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
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

        $registration->update($validated);

        return redirect()->back()->with('success', 'Registration updated successfully.');
    }

    public function destroy(Registration $registration)
    {
        $registration->delete();

        return redirect()->back()->with('success', 'Registration deleted successfully.');
    }
}
