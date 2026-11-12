<?php

use App\Mail\ReviewRequestMail;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    config(['services.whatsapp' => [
        'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
        'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
    ]]);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');

    $this->owner = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);
    $this->property = Property::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Zero Sips',
        'business_type' => 'cafe',
        'review_url' => 'https://g.page/r/zerosips/review',
        'review_requests_enabled' => true,
        'review_request_delay_hours' => 24,
        'review_request_channel' => 'email',
    ]);
});

/** A guest who signed in on the WiFi a month ago and agreed to be contacted. */
function sipper(array $attributes = []): Guest
{
    return Guest::factory()->create(array_merge([
        'property_id' => test()->property->id,
        'first_name' => 'Amina',
        'email' => 'amina@example.com',
        'phone' => null,
        'consented_at' => now()->subMonth(),
        'last_connected' => now()->subMonth(),
        'check_out' => null,
    ], $attributes));
}

it('keeps review requests on WhatsApp until Admin -> Settings switches review emails on', function () {
    $guest = sipper(['phone' => '+254712345678', 'last_connected' => now()->subDays(2)]);

    $this->artisan('reviews:send')->expectsOutput('Review requests sent: 1')->assertSuccessful();

    Mail::assertNothingSent();
    Http::assertSentCount(1);
    $this->actingAs($this->owner)->get(route('host.properties.edit', $this->property))->assertInertia(fn ($page) => $page->where('reviewEmails', null));
    $this->actingAs($this->owner)->post(route('host.properties.review-requests', $this->property))->assertNotFound();
    expect($guest->fresh()->review_requested_at)->not->toBeNull();
});

it('sends a branded email in the property’s name, with a counted review link', function () {
    Setting::setValue('review_emails_enabled', '1');
    $guest = sipper(['last_connected' => now()->subDays(2)]);

    $this->artisan('reviews:send')->expectsOutput('Review requests sent: 1')->assertSuccessful();

    $guest->refresh();
    Mail::assertSent(ReviewRequestMail::class, function (ReviewRequestMail $mail) use ($guest) {
        $html = $mail->render();

        return $mail->hasTo('amina@example.com')
            && $mail->hasFrom(config('mail.from.address'), 'Zero Sips via TenaFi')
            && $mail->hasSubject('Thank you for visiting Zero Sips')
            && str_contains($html, 'Hi Amina, thank you for visiting Zero Sips!')
            && str_contains($html, route('reviews.go', $guest->review_token))
            && str_contains($html, 'Leave a Google review')
            && str_contains($html, '/r/'.$guest->review_token.'/unsubscribe');
    });
    Http::assertNothingSent();
    expect($guest->review_requested_at)->not->toBeNull();

    // Opening the link counts the click and goes to Google.
    $this->get(route('reviews.go', $guest->review_token))->assertRedirect('https://g.page/r/zerosips/review');
    expect($guest->fresh()->review_clicked_at)->not->toBeNull();
});

it('asks only guests it can reach, who agreed and have not opted out', function () {
    Setting::setValue('review_emails_enabled', '1');
    sipper(['first_name' => 'NoEmail', 'email' => null, 'phone' => '+254700000001']);
    sipper(['first_name' => 'NoConsent', 'email' => 'no@example.com', 'consented_at' => null]);
    sipper(['first_name' => 'Stopped', 'email' => 'stop@example.com', 'opted_out_at' => now()]);
    sipper(['first_name' => 'Asked', 'email' => 'asked@example.com', 'review_requested_at' => now()->subWeek()]);
    sipper(['first_name' => 'Yes', 'email' => 'yes@example.com']);

    $this->artisan("reviews:send --everyone --property={$this->property->id}")->expectsOutput('Review requests sent: 1')->assertSuccessful();

    Mail::assertSentCount(1);
    Mail::assertSent(ReviewRequestMail::class, fn ($mail) => $mail->hasTo('yes@example.com'));
});

it('with "both", emails guests who gave an email and WhatsApps the rest', function () {
    Setting::setValue('review_emails_enabled', '1');
    $this->property->update(['review_request_channel' => 'both']);
    sipper(['email' => 'amina@example.com', 'phone' => '+254700000001']);
    sipper(['first_name' => 'Otieno', 'email' => null, 'phone' => '+254700000002']);

    $this->artisan("reviews:send --everyone --property={$this->property->id}")->expectsOutput('Review requests sent: 2')->assertSuccessful();

    Mail::assertSent(ReviewRequestMail::class, fn ($mail) => $mail->hasTo('amina@example.com'));
    Mail::assertSentCount(1);
    Http::assertSentCount(1);
});

it('asks everyone so far from the property page, once, owner only', function () {
    Setting::setValue('review_emails_enabled', '1');
    Setting::setValue('teams_enabled', '1');
    sipper(['email' => 'a@example.com']);
    sipper(['email' => 'b@example.com']);

    $this->actingAs($this->owner)->get(route('host.properties.edit', $this->property))
        ->assertInertia(fn ($page) => $page->where('reviewEmails.waiting', 2)->where('reviewEmails.canSend', true));

    $manager = User::factory()->host()->create(['team_owner_id' => $this->owner->id, 'team_role' => 'manager', 'email_verified_at' => now()]);
    $manager->staffProperties()->sync([$this->property->id]);
    $this->actingAs($manager)->post(route('host.properties.review-requests', $this->property))->assertForbidden();
    Mail::assertNothingSent();

    $this->actingAs($this->owner)->post(route('host.properties.review-requests', $this->property))
        ->assertSessionHas('success', 'Review requests sent to 2 guests.');
    $this->actingAs($this->owner)->post(route('host.properties.review-requests', $this->property))
        ->assertSessionHas('success', 'Review requests sent to 0 guests.');
    Mail::assertSentCount(2);
});

it('lists who would be asked in a dry run, and sends nothing', function () {
    Setting::setValue('review_emails_enabled', '1');
    $guest = sipper();

    $this->artisan("reviews:send --everyone --property={$this->property->id} --dry-run")
        ->expectsOutput('Zero Sips: 1 to ask, channel email.')
        ->expectsOutput('  Amina by email')
        ->expectsOutput('Dry run: nothing was sent.')
        ->assertSuccessful();

    Mail::assertNothingSent();
    expect($guest->fresh()->review_requested_at)->toBeNull();
});

it('stops further messages when the guest unsubscribes', function () {
    $guest = sipper(['review_token' => 'abcd1234']);

    $this->get('/r/abcd1234/unsubscribe')->assertForbidden();
    // Opening the link (as an email scanner would) only asks; the button unsubscribes.
    $link = URL::signedRoute('reviews.unsubscribe', ['token' => 'abcd1234']);
    $this->get($link)->assertOk()->assertSee('Unsubscribe?')->assertSee('Zero Sips');
    expect($guest->fresh()->opted_out_at)->toBeNull();
    $this->post('/r/abcd1234/unsubscribe')->assertForbidden();
    $this->post($link)->assertOk()->assertSee("You're unsubscribed", false);

    expect($guest->fresh()->opted_out_at)->not->toBeNull()
        ->and($guest->fresh()->marketing_opt_in)->toBeFalse();
});

it('only accepts the known channels', function () {
    $this->actingAs($this->owner)->patch(route('host.properties.update', $this->property), ['name' => 'Zero Sips', 'review_request_channel' => 'pigeon'])
        ->assertSessionHasErrors('review_request_channel');
    $this->actingAs($this->owner)->patch(route('host.properties.update', $this->property), ['name' => 'Zero Sips', 'review_request_channel' => 'both'])
        ->assertSessionHasNoErrors();
    expect($this->property->fresh()->review_request_channel)->toBe('both');
});

it('dresses the email like the site’s WiFi page: deep green and gold for the society design', function () {
    $this->property->update(['branding_json' => ['template' => 'society', 'accent' => '#D9BC6A']]);
    $guest = sipper(['review_token' => 'abcd1234']);
    $guest->setRelation('property', $this->property);

    $html = (new ReviewRequestMail($guest, route('reviews.go', 'abcd1234')))->render();

    expect($html)->toContain('background:#0E3A2C')->toContain('background:#D9BC6A')->toContain('Georgia');
});
