<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps /v2 hidden from the public until Admin -> Settings opens it', function () {
    $this->get('/v2')->assertNotFound();
    $this->actingAs(User::factory()->host()->create())->get('/v2')->assertNotFound();

    Setting::setValue('v2_pages_enabled', '1', 'general', 'boolean');
    auth()->logout();

    $this->get('/v2')->assertOk()->assertInertia(fn ($page) => $page->component('Public/V2/Home'));
});

it('lets admins preview /v2 while it is switched off', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/v2')->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/V2/Home'));
});

it('shows the live homepage words and the shared site-wide header and footer, never indexed', function () {
    Setting::setValue('v2_pages_enabled', '1', 'general', 'boolean');

    $this->get('/v2')
        ->assertSee('noindex, nofollow', false)
        ->assertInertia(fn ($page) => $page
            ->where('seo.index', false)
            ->where('sections', fn ($sections) => collect($sections)->pluck('section_key')->contains('hero')
                && collect($sections)->pluck('section_key')->contains('path_cards'))
            ->has('site.header')
            ->has('site.footer'));

    // The live homepage is unchanged and still indexed.
    $this->get('/')->assertInertia(fn ($page) => $page->component('Public/Page')->where('seo.index', true));
});

it('saves the switch from Admin -> Settings', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.update'), ['settings' => ['v2_pages_enabled' => '1']])
        ->assertSessionHasNoErrors();

    expect(Setting::getValue('v2_pages_enabled'))->toBe('1');
});
