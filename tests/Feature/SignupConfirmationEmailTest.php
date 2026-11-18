<?php

use App\Mail\SignupConfirmationMail;
use App\Models\LandingContent;
use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;

/** A sign-up like the website's: hosts or business, with an email. */
function welcomeFor(string $type = 'hosts', string $firstName = 'Wanjiru'): Registration
{
    return new Registration(['type' => $type, 'first_name' => $firstName, 'email' => 'wanjiru@example.com']);
}

it('welcomes a website sign-up with "Welcome to TenaFi." in Glen’s design', function () {
    $mail = new SignupConfirmationMail(welcomeFor());
    $html = $mail->render();

    expect($mail->envelope()->subject)->toBe('Welcome to TenaFi: your sign-up is confirmed')
        ->and($html)->toContain('Sign-up confirmed')
        ->toContain('Welcome to TenaFi.')
        ->toContain('Thanks for signing up, Wanjiru.')
        ->toContain('From a WiFi login to more revenue.')
        ->toContain('We install your TenaFi device')
        // The free months come from the live /hosts pricing (3 on a fresh install).
        ->toContain('Your first 3 months of the Basic plan are free.')
        ->toContain('The same sign-up, tuned to how you work.')
        ->toContain(url('/#how'))
        ->toContain('“Tena” means again in Swahili.')
        ->not->toContain('waitlist')->not->toContain('on the list');
});

it('puts the sign-up’s own kind of place first', function () {
    $hosts = (new SignupConfirmationMail(welcomeFor('hosts')))->render();
    $business = (new SignupConfirmationMail(welcomeFor('business')))->render();

    expect(strpos($hosts, 'Short-term rental operators'))->toBeLessThan(strpos($hosts, 'Business owners'))
        ->and(strpos($business, 'Business owners'))->toBeLessThan(strpos($business, 'Short-term rental operators'));
});

it('shows clients’ WiFi pages from “Trusted by”, a rental first, and leaves the band out without any', function () {
    $partners = LandingSection::where('section_key', 'partners')->whereHas('page', fn ($q) => $q->where('slug', 'home'))->firstOrFail();
    $names = LandingContent::where('section_id', $partners->id)->where('content_key', 'like', 'partners.%.name')->pluck('value', 'content_key');
    $index = fn (string $name) => (int) explode('.', $names->search($name))[1];
    // The admin's uploads (an upload replaces the shipped picture in its slot).
    foreach (['Zero Sips' => '/storage/landing/zero-sips.png', 'Stay Awhile Rentals' => '/storage/landing/stay-awhile.png'] as $name => $path) {
        LandingMedia::updateOrCreate(['section_id' => $partners->id, 'media_key' => 'partner_'.$index($name).'_logo'], ['original_path' => $path, 'mime_type' => 'image/png', 'file_size' => 1000]);
    }
    LandingPage::clearCache();

    $html = (new SignupConfirmationMail(welcomeFor()))->render();
    expect($html)->toContain('Your login page, in your own branding')
        ->toContain(asset('/storage/landing/stay-awhile.png'))
        ->toContain(asset('/storage/landing/zero-sips.png'))
        ->and(substr_count($html, '’s WiFi page"'))->toBeLessThanOrEqual(3)
        ->and(strpos($html, 'Stay Awhile Rentals'))->toBeLessThan(strpos($html, 'Zero Sips'));

    // No WiFi page pictures anywhere: no band.
    LandingMedia::where('media_key', 'like', 'partner_%_logo')->delete();
    LandingPage::clearCache();
    expect((new SignupConfirmationMail(welcomeFor()))->render())->not->toContain('Your login page, in your own branding');
});

it('leaves out the free months when /hosts offers none', function () {
    $pricing = LandingSection::where('section_key', 'pricing')->whereHas('page', fn ($q) => $q->where('slug', 'hosts'))->firstOrFail();
    LandingContent::where('section_id', $pricing->id)->where('content_key', 'free_months')->update(['value' => '0']);
    LandingPage::clearCache();

    expect((new SignupConfirmationMail(welcomeFor()))->render())->not->toContain('of the Basic plan');
});

it('is what a website sign-up gets', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), [
        'type' => 'business', 'firstName' => 'Otieno', 'email' => 'otieno@example.com', 'phone' => '+254722000111',
        'businessName' => 'Kahawa House', 'plan' => 'starter', 'consent' => true,
    ])->assertCreated();

    Mail::assertSent(SignupConfirmationMail::class, fn ($mail) => $mail->hasTo('otieno@example.com'));
});
