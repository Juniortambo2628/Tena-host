<?php

namespace Tests\Feature;

use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use App\Services\CampaignAutomation;
use App\Services\CampaignDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CampaignAutomationTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
            'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
        ]]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $this->host = User::factory()->create(['role' => 'host']);
        $this->property = Property::factory()->create(['user_id' => $this->host->id, 'name' => 'Sunset Villa']);
    }

    private function guest(array $attributes = []): Guest
    {
        static $n = 0;

        return Guest::factory()->create(array_merge([
            'property_id' => $this->property->id, 'first_name' => 'Guest', 'phone' => '+25470000'.str_pad((string) ++$n, 4, '0', STR_PAD_LEFT),
            'consented_at' => now()->subMonth(), 'marketing_opt_in' => true, 'total_visits' => 1,
            'last_connected' => now()->subMonth(), 'check_in' => null, 'check_out' => null,
        ], $attributes));
    }

    private function campaign(array $attributes = []): Campaign
    {
        return Campaign::create(array_merge([
            'user_id' => $this->host->id, 'name' => 'Test', 'type' => 'whatsapp', 'status' => 'draft',
            'content' => 'Hi {guest_name}! Book direct: https://example.com/direct?ref=wifi',
            'trigger_event' => CampaignAutomation::CONNECTS, 'trigger_delay' => 'Instant', 'target_audience' => 'all_guests',
        ], $attributes));
    }

    private function sentTo(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0]['to'])->all();
    }

    public function test_trigger_campaign_waits_for_each_guests_event(): void
    {
        $old = $this->guest();
        $campaign = $this->campaign(['trigger_delay' => '1 Hour']);

        $this->assertSame(0, app(CampaignAutomation::class)->activate($campaign), 'activation does not blast past guests');

        $new = $this->guest(['last_connected' => now()->addMinutes(5)]);
        $this->travel(30)->minutes();
        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 0');

        $this->travel(1)->hours();
        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 1');
        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 0');

        $this->assertSame([ltrim($new->phone, '+')], $this->sentTo());
        $this->assertSame(1, $campaign->fresh()->total_sent);
        $this->assertNull(CampaignRecipient::where('guest_id', $old->id)->first());
    }

    public function test_checkout_and_arrival_triggers(): void
    {
        $leaving = $this->guest(['check_out' => now()->addDay()->toDateString()]);
        $arriving = $this->guest(['check_in' => now()->addDays(2)->toDateString()]);
        $checkout = $this->campaign(['trigger_event' => CampaignAutomation::CHECKOUT_DAY]);
        $arrival = $this->campaign(['trigger_event' => CampaignAutomation::BEFORE_ARRIVAL]);
        app(CampaignAutomation::class)->activate($checkout);
        app(CampaignAutomation::class)->activate($arrival);

        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 0');

        $this->travelTo(now()->addDay()->setTime(10, 0)); // check-out day, after 09:00
        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 1');
        $this->assertTrue(CampaignRecipient::where(['campaign_id' => $checkout->id, 'guest_id' => $leaving->id])->exists());

        $this->travelTo(now()->setTime(15, 0)); // 23h before a 14:00 arrival tomorrow
        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 1');
        $this->assertTrue(CampaignRecipient::where(['campaign_id' => $arrival->id, 'guest_id' => $arriving->id])->exists());
    }

    public function test_scheduled_broadcast_goes_out_at_its_time_once(): void
    {
        $this->guest();
        $this->guest();
        $campaign = $this->campaign(['trigger_event' => CampaignAutomation::CUSTOM_DATE, 'scheduled_at' => now()->addDay()]);

        $this->assertSame(0, app(CampaignAutomation::class)->activate($campaign));
        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 0');

        $this->travel(25)->hours();
        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 2');
        $this->artisan('campaigns:run')->expectsOutput('Campaign messages queued: 0');
        $this->assertNotNull($campaign->fresh()->sent_at);
    }

    public function test_unscheduled_broadcast_sends_on_activation(): void
    {
        $this->guest();
        $campaign = $this->campaign(['trigger_event' => CampaignAutomation::CUSTOM_DATE]);

        $this->actingAs($this->host)->post(route('host.marketing.activate', $campaign->id))
            ->assertSessionHas('success', 'Campaign activated. 1 guests queued for delivery.');
    }

    public function test_audience_segments_and_owner_scoping(): void
    {
        $new = $this->guest(['total_visits' => 1]);
        $returning = $this->guest(['total_visits' => 2]);
        $vip = $this->guest(['total_visits' => 6]);
        $this->guest(['marketing_opt_in' => false]);
        $this->guest(['phone' => null]);
        $otherHostsGuest = Guest::factory()->create(['property_id' => Property::factory()->create()->id, 'phone' => '+254711111111']);

        $ids = fn (string $segment) => app(CampaignDispatcher::class)
            ->audience($this->campaign(['target_audience' => $segment]))->pluck('id')->sort()->values()->all();

        $this->assertSame([$new->id, $returning->id, $vip->id], $ids('all_guests'));
        $this->assertSame([$new->id], $ids('new_guests'));
        $this->assertSame([$returning->id, $vip->id], $ids('returning_guests'));
        $this->assertSame([$vip->id], $ids('vip_guests'));
        $this->assertNotContains($otherHostsGuest->id, $ids('all_guests'));
    }

    public function test_links_are_tracked_and_counted_once(): void
    {
        $this->guest();
        $campaign = $this->campaign(['trigger_event' => CampaignAutomation::CUSTOM_DATE]);
        app(CampaignAutomation::class)->activate($campaign);

        $recipient = CampaignRecipient::sole();
        $body = Http::recorded()->first()[0]['text']['body'];
        $link = route('campaigns.click', ['token' => $recipient->token, 'n' => 0]);
        $this->assertSame("Hi Guest! Book direct: {$link}\n\nReply STOP to opt out.", $body);

        $this->get($link)->assertRedirect('https://example.com/direct?ref=wifi');
        $this->get($link)->assertRedirect('https://example.com/direct?ref=wifi');

        $campaign->refresh();
        $this->assertSame([1, 1], [$campaign->total_clicked, $campaign->total_opened]);
        $this->get(route('campaigns.click', ['token' => $recipient->token, 'n' => 5]))->assertNotFound();
    }

    public function test_email_opens_are_tracked_and_images_left_alone(): void
    {
        $this->guest(['email' => 'g@example.com']);
        $campaign = $this->campaign([
            'type' => 'email', 'trigger_event' => CampaignAutomation::CUSTOM_DATE,
            'content' => '<p><img src="https://cdn.example.com/a.png"><a href="https://example.com/offer">Offer</a></p>',
        ]);
        app(CampaignAutomation::class)->activate($campaign);

        $recipient = CampaignRecipient::sole();
        $html = (new CampaignEmail($campaign, $recipient->guest, $recipient))->render();
        $this->assertStringContainsString('src="https://cdn.example.com/a.png"', $html);
        $this->assertStringContainsString('href="'.route('campaigns.click', ['token' => $recipient->token, 'n' => 0]).'"', $html);

        $this->get(route('campaigns.open', $recipient->token))->assertOk()->assertHeader('Content-Type', 'image/gif');
        $this->get(route('campaigns.click', ['token' => $recipient->token, 'n' => 0]))->assertRedirect('https://example.com/offer');
        $this->assertSame([1, 1], [$campaign->fresh()->total_opened, $campaign->fresh()->total_clicked]);
    }

    public function test_delay_parsing(): void
    {
        $this->assertSame([0, 60, 360, 1440, 4320, 10080],
            array_map([CampaignAutomation::class, 'delayMinutes'], ['Instant', '1 Hour', '6 Hours', '24 Hours', '3 Days', '7 Days']));
    }
}
