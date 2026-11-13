<?php

use App\Services\Cms\PageBlueprint;

/*
 * A fresh install takes its copy from the blueprint, so the blueprint must
 * carry the live offer (book a demo since November 2026), never the old
 * Founding 20 / paid one or the December free trial.
 */
it('never brings back the old Founding 20 offer on a fresh install', function () {
    $copy = json_encode(PageBlueprint::definitions(), JSON_UNESCAPED_UNICODE);

    expect($copy)->not->toContain('Founding 20')
        ->not->toContain('first two months free')
        ->not->toContain('12-month plan')
        ->not->toContain('Billing begins')
        ->not->toContain('Apply for a spot');
});

it('offers a demo, not the December free trial, on every audience page', function (string $slug) {
    $pages = PageBlueprint::definitions();
    $banner = $pages[$slug]['cta_banner__founding']['content'];
    $pricing = $pages[$slug]['pricing']['content'];
    $signup = $pages[$slug]['signup']['content'];

    expect($banner['badge'])->toBe('Book a demo')
        ->and($banner['body'])->toContain('call or video')->toContain('30 days')->toContain('2 months free')
        ->and(strtolower($pricing['subtitle']))->toContain('month to month')
        ->and($pricing['footnote'])->toContain('30 days')->not->toContain('16 November')
        ->and($signup['submit_label'])->toBe('Book my demo')
        ->and($signup['badge'])->toBe('Book a demo')
        ->and(json_encode($pages[$slug], JSON_UNESCAPED_UNICODE))->not->toContain('free trial')->not->toContain('Free trial');
})->with(['hosts', 'business']);

it('leaves the discount threshold as a marked TODO, and never mentions installation next to a price', function () {
    $source = file_get_contents(database_path('blueprints/public_pages.php'));

    expect($source)->toContain('TODO(Glen): does the multi-unit discount start at 5 or 10 units?')
        // Device prices stay as they are; installation isn't itemised (Nov 2026).
        ->toContain('Installation isn\'t itemised')
        // No copy announces a fee nobody has decided on.
        ->and(json_encode(PageBlueprint::definitions()))->not->toContain('2,500');
});
