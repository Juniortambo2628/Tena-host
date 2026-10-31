<?php

use App\Models\Guest;
use App\Models\Property;
use App\Models\Registration;
use App\Models\Setting;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('is switched off until Admin -> Settings turns it on', function () {
    $this->get('/demo')->assertNotFound();
    $this->get('/')->assertInertia(fn ($page) => $page->where('demo', false));
});

it('shows the real WiFi page for each sample place, noindexed, with a switcher', function (string $type, string $name, string $template) {
    Setting::setValue('demo_enabled', '1');

    $this->get("/demo?type={$type}")->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('content="noindex"', false)
        ->assertSee($name)
        ->assertSee("splash--{$template}", false)
        ->assertSee('Demo · nothing you type is saved')
        ->assertSee(route('demo.connect', ['type' => $type]), false);
})->with([
    ['rental', 'The Loft Nairobi', 'bento'],
    ['cafe', 'Kahawa House', 'brew'],
    ['salon', 'Glow Studio', 'bloom'],
    ['event', 'Kona Design Week', 'nocturne'],
]);

it('shows the connected page but saves and sends nothing', function () {
    Setting::setValue('demo_enabled', '1');
    Mail::fake();
    $this->mock(UnifiService::class, fn ($mock) => $mock->shouldNotReceive('authorizeGuest'));

    $this->post('/demo?type=cafe', ['first_name' => 'Wanjiru', 'phone' => '712345678', 'consent' => '1', 'marketing_opt_in' => '1'])
        ->assertOk()
        ->assertSee("You're connected", false)
        ->assertSee('Kahawa House');

    expect(Guest::count())->toBe(0)
        ->and(Property::count())->toBe(0)
        ->and(Registration::count())->toBe(0);
    Mail::assertNothingSent();
});

it('falls back to the rental for an unknown type', function () {
    Setting::setValue('demo_enabled', '1');
    $this->get('/demo?type=spaceport')->assertOk()->assertSee('The Loft Nairobi');
});
