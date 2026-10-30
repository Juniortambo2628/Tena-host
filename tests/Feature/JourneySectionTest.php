<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function journeyContent(string $slug): array
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', $slug))->where('section_key', 'journey')->firstOrFail()
        ->contents()->pluck('value', 'content_key')->all();
}

it('sits directly above pricing on both audience pages and books the right free trial', function (string $slug, string $book) {
    $order = LandingPage::firstWhere('slug', $slug)->sections()->where('is_active', true)->orderBy('sort_order')->pluck('section_key')->all();
    expect($order[array_search('pricing', $order) - 1])->toBe('journey');

    $content = journeyContent($slug);
    expect($content['eyebrow'])->toBe('Log on. Opt in. Come back.')
        ->and($content['cta_label'])->toBe('Book my free trial')
        ->and($content['cta_url'])->toBe($book)
        ->and($content)->toHaveKeys(['steps.4.title', 'steps.4.screen', 'numbers.3.value', 'outcomes.3.title']);
})->with([['hosts', '/hosts#join'], ['business', '/business#signup']]);

it('keeps review requests reward-free and links nowhere near the AI designer', function (string $slug) {
    $content = journeyContent($slug);
    $review = collect($content)->filter(fn ($v, $k) => str_starts_with($k, 'steps.') && str_ends_with($k, '.screen') && str_contains(strtolower($v), 'review'))->implode("\n");

    // The review message lines never mention a reward; the book-direct offer is its own message.
    foreach (explode("\n", $review) as $line) {
        if (str_contains(strtolower($line), 'review')) {
            expect(strtolower($line))->not->toContain('%')->not->toContain('discount')->not->toContain('5-star')->not->toContain('free');
        }
    }
    expect(implode(' ', $content))->not->toContain('/design')->not->toContain('Design yours');
    expect($content['example_title'])->toContain('Example');
})->with(['hosts', 'business']);

it('adds the section to a live page without bringing back removed rows or moving other sections', function () {
    $hosts = LandingPage::firstWhere('slug', 'hosts');
    LandingSection::where('page_id', $hosts->id)->where('section_key', 'journey')->get()->each->delete();
    // The admin removed an FAQ and edited the pricing title.
    $faq = LandingSection::where('page_id', $hosts->id)->where('section_key', 'faq')->first();
    $faq->contents()->where('content_key', 'like', 'items.5.%')->delete();
    LandingSection::where('page_id', $hosts->id)->where('section_key', 'pricing')->first()
        ->contents()->where('content_key', 'title')->update(['value' => 'Admin pricing title']);
    $before = $hosts->sections()->orderBy('sort_order')->pluck('section_key')->all();

    (require database_path('migrations/2026_10_30_000003_first_month_journey.php'))->up();

    $after = $hosts->sections()->orderBy('sort_order')->pluck('section_key')->all();
    expect(array_values(array_diff($after, ['journey'])))->toBe($before)
        ->and($after[array_search('pricing', $after) - 1])->toBe('journey')
        ->and($faq->contents()->where('content_key', 'like', 'items.5.%')->count())->toBe(0)
        ->and(LandingSection::where('page_id', $hosts->id)->where('section_key', 'pricing')->first()->contents()->where('content_key', 'title')->value('value'))->toBe('Admin pricing title');

    // Idempotent.
    PageBlueprint::addSections('hosts', ['journey' => 'pricing']);
    expect(LandingSection::where('page_id', $hosts->id)->where('section_key', 'journey')->count())->toBe(1);
});

it('renders on the audience pages', function (string $url) {
    $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
        ->where('sections', fn ($sections) => collect($sections)->contains('section_key', 'journey')));
})->with(['/hosts', '/business']);
