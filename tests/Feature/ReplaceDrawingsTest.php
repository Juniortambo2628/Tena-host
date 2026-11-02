<?php

use App\Models\LandingSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lets an admin upload a screenshot in place of a drawing, shown on the public page', function (string $sectionKey, string $mediaKey) {
    Storage::fake('public');
    $section = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'home'))->where('section_key', $sectionKey)->firstOrFail();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.landing.media.upload', $section), [
            'media_key' => $mediaKey,
            'file' => UploadedFile::fake()->image('screenshot.png', 600, 1200),
        ])
        ->assertSessionHasNoErrors();

    $this->get('/')->assertInertia(fn ($page) => $page->where('sections', fn ($sections) => filled(
        collect($sections)->firstWhere('section_key', $sectionKey)['media'][$mediaKey] ?? null
    )));
})->with([
    'hero WiFi page' => ['hero', 'visual_shot'],
    'audience card' => ['path_cards', 'card_1_shot'],
    'feature row' => ['detailed_features__home', 'section_2_shot'],
]);
