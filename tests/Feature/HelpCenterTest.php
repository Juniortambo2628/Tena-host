<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('opens the Help Center for every kind of dashboard user, with how to reach support', function (string $role) {
    Setting::setValue('support_email', 'help@example.com');
    $user = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);

    $this->actingAs($user)->get(route('help'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Profile/Help')->where('support.email', 'help@example.com'));
})->with(['host', 'admin', 'staff']);

it('needs a login', function () {
    $this->get(route('help'))->assertRedirect(route('login'));
});

it('has a guide for every feature area, each with a real category', function () {
    $source = file_get_contents(resource_path('js/lib/helpArticles.js'));
    preg_match_all("/category: '([a-z]+)'/", $source, $used);
    preg_match_all("/\\{ id: '([a-z]+)', label:/", $source, $defined);

    expect(array_diff(array_unique($used[1]), $defined[1]))->toBe([]);
    foreach (['splash-studio', 'access-points', 'guest-list', 'review-requests', 'campaigns', 'paid-extras', 'events-overview', 'event-sponsors', 'pay-per-event', 'staff-wifi', 'visitor-log', 'teams', 'billing', 'admin-connect-controller', 'admin-monitoring', 'contact'] as $slug) {
        expect($source)->toContain("slug: '{$slug}'");
    }

    // Every route a guide links to exists.
    preg_match_all("/route: '([a-z.\\-]+)'/", $source, $routes);
    foreach (array_unique($routes[1]) as $name) {
        expect(Route::has($name))->toBeTrue("Missing route {$name}");
    }
});
