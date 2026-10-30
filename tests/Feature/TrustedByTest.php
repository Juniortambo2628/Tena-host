<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function homeSection(string $key): LandingSection
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'home'))->where('section_key', $key)->firstOrFail();
}

it('adds kind and type/area to live partner rows without touching admin copy, and moves the section below the hero', function () {
    $partners = homeSection('partners');
    // The live site before this change: no new fields, an edited title, an extra partner, and the old order.
    $partners->contents()->where('content_key', 'like', 'partners.%.kind')->delete();
    $partners->contents()->where('content_key', 'like', 'partners.%.type_area')->delete();
    $partners->contents()->where('content_key', 'title')->update(['value' => 'Loved by our clients']);
    $partners->contents()->create(['content_key' => 'partners.5.name', 'value' => 'Mama Oliech', 'type' => 'text']);
    homeSection('path_cards')->update(['sort_order' => 2]);
    $partners->update(['sort_order' => 3]);

    (require database_path('migrations/2026_10_30_000002_trusted_by_wifi_pages.php'))->up();

    $content = $partners->contents()->pluck('value', 'content_key');
    expect($content['title'])->toBe('Loved by our clients')
        ->and($content['partners.3.kind'])->toBe('hosts')
        ->and($content['partners.3.type_area'])->toBe("Serviced apartments \u{00B7} Nairobi")
        ->and($content['partners.2.kind'])->toBe('business')
        ->and($content['partners.5.kind'])->toBe('');

    $order = LandingPage::firstWhere('slug', 'home')->sections()->orderBy('sort_order')->pluck('section_key')->all();
    expect(array_slice($order, 0, 4))->toBe(['seo', 'hero', 'partners', 'path_cards']);

    // Running it again changes nothing.
    (require database_path('migrations/2026_10_30_000002_trusted_by_wifi_pages.php'))->up();
    expect(LandingContent::where('section_id', $partners->id)->where('content_key', 'partners.3.kind')->count())->toBe(1);
});

it('sends partner kinds, areas and screenshots to the homepage', function () {
    homeSection('partners')->media()->create(['media_key' => 'partner_3_logo', 'original_path' => '/storage/trusted/stay-awhile.webp', 'mime_type' => 'image/webp', 'file_size' => 1, 'sort_order' => 0]);

    $this->get('/')->assertInertia(fn ($page) => $page->where('sections', function ($sections) {
        $s = collect($sections)->firstWhere('section_key', 'partners');

        return $s['content']['partners.3.kind'] === 'hosts'
            && $s['content']['partners.0.kind'] === 'business'
            && str_contains(json_encode($s['media']), 'stay-awhile.webp');
    }));
});
