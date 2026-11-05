<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// The "Replace picture" buttons show only for admins: the page needs the
// signed-in user's role and each section's id (for /admin/landing?section=).
it('gives an admin on the public site what the Replace picture buttons need', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.role', 'admin')
            ->where('sections', fn ($sections) => collect($sections)->every(fn ($s) => is_int($s['id'] ?? null))));
});

it('gives visitors and hosts no admin role, so they never see the buttons', function () {
    $this->get('/')->assertInertia(fn ($page) => $page->where('auth.user', null));

    $this->actingAs(User::factory()->create(['role' => 'host']))
        ->get('/hosts')
        ->assertInertia(fn ($page) => $page->where('auth.user.role', 'host'));
});

it('opens the editor page for the section and slot a button links to', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/landing?page=hosts&section=1&slot=visual_shot')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Landing/Index')->where('currentPage', 'hosts'));
});
