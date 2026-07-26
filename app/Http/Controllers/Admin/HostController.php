<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HostController extends Controller
{
    public function index()
    {
        $hosts = User::where('role', 'host')
            ->withCount('properties')
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Hosts/Index', [
            'hosts' => $hosts,
        ]);
    }

    public function show(User $user)
    {
        $user->loadCount('properties');

        return Inertia::render('Admin/Hosts/Show', [
            'host' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Host updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Host deleted successfully.');
    }
}
