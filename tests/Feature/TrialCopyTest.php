<?php

use App\Services\Cms\PageBlueprint;

/*
 * A fresh install takes its copy from the blueprint, so the blueprint must
 * carry the live free-trial offer, never the old Founding 20 / paid one.
 */
it('never brings back the old Founding 20 offer on a fresh install', function () {
    $copy = json_encode(PageBlueprint::definitions(), JSON_UNESCAPED_UNICODE);

    expect($copy)->not->toContain('Founding 20')
        ->not->toContain('first two months free')
        ->not->toContain('12-month plan')
        ->not->toContain('Billing begins')
        ->not->toContain('Apply for a spot');
});

it('offers the December free trial on every audience page', function (string $slug) {
    $pages = PageBlueprint::definitions();
    $banner = $pages[$slug]['cta_banner__founding']['content'];
    $pricing = $pages[$slug]['pricing']['content'];
    $signup = $pages[$slug]['signup']['content'];

    expect($banner['title'])->toContain('December')
        ->and($banner['body'])->toContain('16 November')->toContain('30 days')->toContain('2 months free')
        ->and($pricing['subtitle'])->toContain('Free for December, then month to month')
        ->and($pricing['footnote'])->toContain('16 November')->toContain('30 days')
        ->and($signup['submit_label'])->toBe('Book my free trial')
        ->and($signup['badge'])->toBe('Free trial');
})->with(['hosts', 'business']);

it('leaves the undecided installation fee and discount threshold as marked TODOs', function () {
    $source = file_get_contents(database_path('blueprints/public_pages.php'));

    expect($source)->toContain('TODO(Glen): does the KES 2,500 installation fee apply after the trial?')
        ->toContain('TODO(Glen): does the multi-unit discount start at 5 or 10 units?')
        // No copy announces a fee nobody has decided on.
        ->and(json_encode(PageBlueprint::definitions()))->not->toContain('2,500');
});
