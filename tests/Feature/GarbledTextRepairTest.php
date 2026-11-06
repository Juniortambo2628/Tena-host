<?php

use App\Models\LandingContent;
use App\Models\LandingSection;
use App\Models\Setting;
use App\Services\Cms\GarbledText;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function contentRow(string $slug, string $section, string $key): LandingContent
{
    return LandingContent::whereHas('section', fn ($q) => $q->where('section_key', $section)->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', $key)->sole();
}

function runRepair(): void
{
    (require database_path('migrations/2026_11_15_000001_repair_garbled_cms_text.php'))->up();
}

it('restores text the old server garbled, on the live pages and their v2 copies', function () {
    $live = contentRow('hosts', 'stats__problem', 'title');
    $copy = contentRow('v2-hosts', 'stats__problem', 'title');
    $original = $live->value;
    expect($original)->toContain('’');

    // What the old server saved: every byte of a special character became "?".
    $garbled = preg_replace_callback('/[^\x00-\x7F]/u', fn ($m) => str_repeat('?', strlen($m[0])), $original);
    $live->update(['value' => $garbled]);
    $copy->update(['value' => $garbled]);

    // An admin-edited text (not the blueprint's wording) is mended from context.
    $edited = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'business'))->first();
    LandingContent::create(['section_id' => $edited->id, 'content_key' => 'test_note', 'value' => '<p>From&nbsp;caf??s:&nbsp;you&nbsp;don???t&nbsp;pay&nbsp;???&nbsp;ever.&nbsp;???Hi&nbsp;Brian???</p>', 'type' => 'text']);
    Setting::setValue('waitlist_confirmation_subject', 'You???re on the list');

    runRepair();

    expect($live->fresh()->value)->toBe($original)
        ->and($copy->fresh()->value)->toBe($original)
        ->and(LandingContent::where('content_key', 'test_note')->sole()->value)
        ->toBe('<p>From&nbsp;cafés:&nbsp;you&nbsp;don’t&nbsp;pay&nbsp;—&nbsp;ever.&nbsp;“Hi&nbsp;Brian”</p>')
        ->and(Setting::getValue('waitlist_confirmation_subject'))->toBe('You’re on the list')
        ->and(LandingContent::where('value', 'like', '%???%')->exists())->toBeFalse();
});

it('leaves ordinary question marks alone', function () {
    expect(GarbledText::isGarbled('Questions? WhatsApp us.'))->toBeFalse()
        ->and(GarbledText::mend('How much does it cost?'))->toBe('How much does it cost?');
});
