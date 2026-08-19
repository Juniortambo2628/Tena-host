<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(15);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => [
                'total' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'hosts' => User::where('role', 'host')->count(),
                'guests' => User::where('role', 'guest')->count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,host,staff,guest',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'username' => strtolower($validated['first_name'].'.'.$validated['last_name']),
            'email_verified_at' => now(),
        ]);

        NotificationService::userCreated($user, auth()->user());

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $oldRole = $user->role;

        $validated = $request->validate([
            'role' => 'required|in:admin,host,staff,guest',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
        ]);

        $user->update($validated);

        if (isset($validated['role']) && $validated['role'] !== $oldRole) {
            NotificationService::roleChanged($user, $oldRole, $validated['role'], auth()->user());
        }

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
