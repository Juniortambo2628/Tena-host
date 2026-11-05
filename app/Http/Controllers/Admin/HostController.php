<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccountType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HostController extends Controller
{
    /** Accounts: rentals, businesses and events, filterable by type. */
    public function index(Request $request)
    {
        $type = in_array($request->query('type'), User::ACCOUNT_TYPES, true) ? $request->query('type') : null;

        $hosts = User::where('role', 'host')->whereNull('team_owner_id')
            ->when($type, fn ($q) => $q->where('account_type', $type))
            ->withCount('properties')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Hosts/Index', [
            'hosts' => $hosts,
            'filters' => ['type' => $type],
            'typeCounts' => User::where('role', 'host')->whereNull('team_owner_id')->selectRaw('account_type, count(*) as total')->groupBy('account_type')->pluck('total', 'account_type'),
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
            'account_type' => 'sometimes|in:'.implode(',', User::ACCOUNT_TYPES),
            // Event accounts: venue, organiser or both (defaults and wording only).
            'account_profile' => 'sometimes|nullable|in:'.implode(',', AccountType::EVENT_PROFILES),
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Account updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Account deleted.');
    }
}
