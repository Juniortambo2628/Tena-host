<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PolicyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PolicyDocumentController extends Controller
{
    public function index()
    {
        $policies = PolicyDocument::latest()->get();

        return Inertia::render('Admin/Policies/Index', [
            'policies' => $policies,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Policies/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'content' => 'required|string',
            'type' => 'required|in:privacy_policy,terms_of_service,cookie_policy,refund_policy,acceptable_use,data_processing,other',
            'is_published' => 'boolean',
            'version' => 'nullable|string|max:20',
            'effective_date' => 'nullable|date',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['last_reviewed_at'] = now();
        $validated['last_reviewed_by'] = auth()->user()->name ?? 'Admin';

        PolicyDocument::create($validated);

        return redirect()->route('admin.policies.index')
            ->with('success', 'Policy document created successfully.');
    }

    public function show(PolicyDocument $policy)
    {
        return Inertia::render('Admin/Policies/Show', [
            'policy' => $policy,
        ]);
    }

    public function edit(PolicyDocument $policy)
    {
        return Inertia::render('Admin/Policies/Edit', [
            'policy' => $policy,
        ]);
    }

    public function update(Request $request, PolicyDocument $policy)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'content' => 'required|string',
            'type' => 'required|in:privacy_policy,terms_of_service,cookie_policy,refund_policy,acceptable_use,data_processing,other',
            'is_published' => 'boolean',
            'version' => 'nullable|string|max:20',
            'effective_date' => 'nullable|date',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['last_reviewed_at'] = now();
        $validated['last_reviewed_by'] = auth()->user()->name ?? 'Admin';

        $policy->update($validated);

        return redirect()->route('admin.policies.index')
            ->with('success', 'Policy document updated successfully.');
    }

    public function destroy(PolicyDocument $policy)
    {
        $policy->delete();

        return redirect()->route('admin.policies.index')
            ->with('success', 'Policy document deleted successfully.');
    }

    public function togglePublish(PolicyDocument $policy)
    {
        $policy->update([
            'is_published' => ! $policy->is_published,
            'last_reviewed_at' => now(),
            'last_reviewed_by' => auth()->user()->name ?? 'Admin',
        ]);

        return back()->with('success', 'Policy status updated.');
    }
}
