<?php

use App\Mail\CampaignEmail;
use App\Models\AccessPoint;
use App\Models\Amenity;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Models\Order;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->host = User::factory()->host()->create();
    $this->property = Property::factory()->create(['user_id' => $this->host->id, 'name' => 'Sunset Villa']);
    $this->other = Property::factory()->create(['name' => 'Someone Else Lodge']);
});

// ─── Adding guests by hand ────────────────────────────────────────────────────

it('adds a guest with only a first name and a local phone number', function () {
    $this->actingAs($this->host)->post(route('host.guests.store'), [
        'property_id' => $this->property->id, 'first_name' => 'Wanjiru', 'phone' => '0712 345 678',
        'contact_consent' => '1', 'marketing_opt_in' => '1',
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    $guest = Guest::sole();
    expect($guest->phone)->toBe('+254712345678')
        ->and($guest->source)->toBe('Manual')
        ->and($guest->marketing_opt_in)->toBeTrue()
        ->and($guest->consented_at)->not->toBeNull();
});

it('updates the existing guest instead of adding a duplicate', function () {
    Guest::factory()->create(['property_id' => $this->property->id, 'first_name' => 'W', 'phone' => '+254712345678']);

    $this->actingAs($this->host)->post(route('host.guests.store'), [
        'property_id' => $this->property->id, 'first_name' => 'Wanjiru', 'phone' => '0712345678', 'contact_consent' => '1',
    ])->assertSessionHasNoErrors();

    expect(Guest::count())->toBe(1)->and(Guest::first()->first_name)->toBe('Wanjiru');
});

it('will not add guests to another host\'s property or accept a bad phone', function () {
    $this->actingAs($this->host)->post(route('host.guests.store'), [
        'property_id' => $this->other->id, 'first_name' => 'X', 'phone' => '12', 'contact_consent' => '1',
    ])->assertSessionHasErrors(['property_id', 'phone']);

    expect(Guest::count())->toBe(0);
});

it('can add a guest to a property created moments ago', function () {
    $this->actingAs($this->host)->get(route('host.guests.index'))->assertOk(); // warms the property-id cache
    $this->actingAs($this->host)->post(route('host.properties.store'), ['name' => 'Brand New', 'occupancy_threshold' => 10]);
    $new = Property::where('name', 'Brand New')->sole();

    $this->actingAs($this->host)->get(route('host.properties.show', $new))->assertOk();
    $this->actingAs($this->host)->post(route('host.guests.store'), [
        'property_id' => $new->id, 'first_name' => 'Amina', 'email' => 'amina@example.com', 'contact_consent' => '1',
    ])->assertSessionHasNoErrors();
});

it('finds guests by phone in any common format', function () {
    Guest::factory()->create(['property_id' => $this->property->id, 'first_name' => 'Wanjiru', 'phone' => '+254712345678']);
    Guest::factory()->create(['property_id' => $this->property->id, 'first_name' => 'Otieno', 'phone' => '+254733000000']);

    $this->actingAs($this->host)->get(route('host.guests.index', ['search' => '0712 345']))
        ->assertInertia(fn ($page) => $page->has('guests.data', 1)->where('guests.data.0.first_name', 'Wanjiru'));
    $this->actingAs($this->host)->get(route('host.guests.index', ['search' => 'Otieno']))
        ->assertInertia(fn ($page) => $page->has('guests.data', 1));
});

// ─── Edit screens send PATCH ──────────────────────────────────────────────────

it('accepts PATCH from the guest, order, amenity and access point screens', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id, 'is_active' => true]);
    $order = Order::factory()->create(['property_id' => $this->property->id, 'guest_id' => $guest->id, 'amenity_id' => $amenity->id, 'status' => 'pending']);
    $ap = AccessPoint::factory()->create(['property_id' => $this->property->id, 'status' => 'online']);

    $this->actingAs($this->host)->patch(route('host.guests.update', $guest), ['first_name' => 'Renamed', 'phone' => '0712 000 111'])->assertRedirect();
    $this->actingAs($this->host)->patch(route('host.orders.update', $order), ['status' => 'fulfilled'])->assertRedirect();
    $this->actingAs($this->host)->patch(route('host.amenities.update', $amenity), ['is_active' => false])->assertRedirect();
    $this->actingAs($this->host)->patch(route('host.access-points.update', $ap), ['status' => 'offline'])->assertRedirect();

    expect($guest->fresh()->first_name)->toBe('Renamed')
        ->and($guest->fresh()->phone)->toBe('+254712000111')
        ->and($order->fresh()->status)->toBe('fulfilled')
        ->and($amenity->fresh()->is_active)->toBeFalsy()
        ->and($ap->fresh()->status)->toBe('offline');
});

it('will not create orders against another host\'s guests or extras', function () {
    $theirGuest = Guest::factory()->create(['property_id' => $this->other->id]);
    $theirAmenity = Amenity::factory()->create(['property_id' => $this->other->id]);

    // Orders are created through the guest-portal route, which any signed-in user can reach.
    $this->actingAs($this->host)->post(route('guest.orders.store'), [
        'property_id' => $this->property->id, 'guest_id' => $theirGuest->id, 'amenity_id' => $theirAmenity->id, 'total' => 100,
    ])->assertSessionHasErrors(['guest_id', 'amenity_id']);

    expect(Order::count())->toBe(0);
});

it('keeps hosts to their own properties in the guest portal and guidebook', function () {
    $this->actingAs($this->host)->get(route('guest.portal', ['p' => $this->property->id]))->assertOk();
    $this->actingAs($this->host)->get(route('guest.portal', ['p' => $this->other->id]))->assertForbidden();
    $this->actingAs($this->host)->get(route('guest.guidebook', ['p' => $this->other->id]))->assertForbidden();
});

// ─── Campaign Studio ──────────────────────────────────────────────────────────

it('launches a campaign straight from the studio', function () {
    $this->actingAs($this->host)->post(route('host.marketing.store'), [
        'name' => 'Return stay', 'type' => 'whatsapp', 'content' => 'Hi {guest_name}!',
        'trigger_event' => 'Guest Connects to WiFi', 'trigger_delay' => 'Instant', 'target_audience' => 'all_guests', 'launch' => true,
    ])->assertRedirect(route('host.marketing.index'));

    expect(Campaign::sole())->status->toBe('active')->activated_at->not->toBeNull();
});

it('keeps an empty campaign as a draft and never sets active without activation', function () {
    $this->actingAs($this->host)->post(route('host.marketing.store'), ['name' => 'Empty', 'type' => 'sms', 'launch' => true]);
    $campaign = Campaign::sole();
    expect($campaign->status)->toBe('draft');

    $this->actingAs($this->host)->put(route('host.marketing.update', $campaign), ['status' => 'active'])->assertSessionHasErrors('status');
    expect($campaign->fresh()->status)->toBe('draft');
});

it('rejects another host\'s property as a campaign audience', function () {
    $this->actingAs($this->host)->post(route('host.marketing.store'), [
        'name' => 'Sneaky', 'type' => 'sms', 'audience_property_id' => $this->other->id,
    ])->assertSessionHasErrors('audience_property_id');
});

it('previews an unsaved email for a sample guest', function () {
    $this->actingAs($this->host)->post(route('host.marketing.preview'), [
        'subject' => 'Hello {guest_name}', 'content' => "Hi {guest_name}!\nSee https://example.com/offer", 'property_id' => $this->property->id,
    ])->assertOk()->assertSee('Hi Wanjiru!<br>', false)->assertSee('<a href="https://example.com/offer">', false)->assertSee('Sunset Villa');

    $this->actingAs($this->host)->post(route('host.marketing.preview'), ['property_id' => $this->other->id])->assertSessionHasErrors('property_id');
});

it('sends plain-text emails with placeholders, tracked links and a working unsubscribe', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id, 'first_name' => 'Wanjiru', 'email' => 'w@example.com', 'marketing_opt_in' => true]);
    $campaign = Campaign::factory()->create(['user_id' => $this->host->id, 'type' => 'email', 'content' => "Hi {guest_name} <3\nBook: https://example.com/book"]);
    $recipient = CampaignRecipient::create(['campaign_id' => $campaign->id, 'guest_id' => $guest->id]);

    $html = (new CampaignEmail($campaign, $guest, $recipient))->render();
    expect($html)->toContain('Hi Wanjiru &lt;3<br>')
        ->toContain(route('campaigns.click', ['token' => $recipient->token, 'n' => 0]))
        ->not->toContain('href="https://example.com/book"');

    $this->get(route('campaigns.click', ['token' => $recipient->token, 'n' => 0]))->assertRedirect('https://example.com/book');

    $this->get(route('campaigns.unsubscribe', ['token' => $recipient->token]))->assertForbidden(); // unsigned
    $this->get(URL::signedRoute('campaigns.unsubscribe', ['token' => $recipient->token]))->assertOk()->assertSee("You're unsubscribed", false);
    expect($guest->fresh()->opted_out_at)->not->toBeNull()->and($guest->fresh()->marketing_opt_in)->toBeFalse();
});

it('offers splash offers as campaign starters in the studio', function () {
    $this->property->update(['branding_json' => ['template' => 'bento', 'offers' => [['title' => '20% off your next stay']]]]);

    $this->actingAs($this->host)->get(route('host.marketing.builder'))
        ->assertInertia(fn ($page) => $page->component('Host/Marketing/Builder')
            ->where('splashOffers.0.title', '20% off your next stay')
            ->where('splashOffers.0.property', 'Sunset Villa')
            ->has('channels.whatsapp'));
});
