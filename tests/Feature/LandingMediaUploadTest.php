<?php

use App\Models\LandingMedia;
use App\Models\LandingSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function heroSection(): LandingSection
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'home'))->where('section_key', 'hero')->firstOrFail();
}

function uploadHeroPhoto(UploadedFile $file)
{
    return test()->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.landing.media.upload', heroSection()), ['media_key' => 'main_image', 'file' => $file]);
}

it('replaces a photo: one upload per slot, and the website shows the new one', function () {
    Storage::fake('public');

    uploadHeroPhoto(UploadedFile::fake()->image('first.jpg', 1200, 800))->assertOk();
    uploadHeroPhoto(UploadedFile::fake()->image('second.jpg', 1200, 800))->assertOk();

    $slot = LandingMedia::where('section_id', heroSection()->id)->where('media_key', 'main_image')->get();
    expect($slot)->toHaveCount(1)
        ->and($slot->first()->original_path)->toContain('second_');

    $this->get('/')->assertInertia(fn ($page) => $page->where('sections', fn ($sections) => str_contains(
        (string) (collect($sections)->firstWhere('section_key', 'hero')['media']['main_image'] ?? ''), 'second_'
    )));
});

it('keeps the current photo when an upload fails', function () {
    Storage::fake('public');
    uploadHeroPhoto(UploadedFile::fake()->image('current.jpg', 1200, 800))->assertOk();

    // Not an image or video: the upload service refuses it.
    uploadHeroPhoto(UploadedFile::fake()->create('notes.txt', 4, 'text/plain'))->assertStatus(500)->assertJsonPath('error', fn ($e) => str_contains($e, 'Unsupported file type'));

    expect(LandingMedia::where('section_id', heroSection()->id)->where('media_key', 'main_image')->value('original_path'))
        ->toContain('current_');
});
