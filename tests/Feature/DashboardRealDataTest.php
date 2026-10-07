<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use App\Services\PropertyStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRealDataTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        $this->host = User::factory()->create(['role' => 'host']);
        $this->property = Property::factory()->create(['user_id' => $this->host->id, 'name' => 'Sunset Villa', 'review_url' => null]);
    }

    public function test_property_stats_come_from_bookings_and_access_points(): void
    {
        AccessPoint::factory()->create(['property_id' => $this->property->id, 'status' => 'online']);
        AccessPoint::factory()->create(['property_id' => $this->property->id, 'status' => 'offline']);

        $this->assertNull(app(PropertyStats::class)->forHost($this->host)['occupancy'], 'no bookings, no number');

        // 15 of the last 30 nights booked.
        Guest::factory()->create(['property_id' => $this->property->id,
            'check_in' => now()->subDays(20)->toDateString(), 'check_out' => now()->subDays(5)->toDateString()]);

        $this->actingAs($this->host)->get(route('host.properties.index'))->assertInertia(fn ($page) => $page
            ->where('stats.occupancy', 50)
            ->where('stats.aps_online', 1)
            ->where('stats.aps_total', 2));
    }

    public function test_properties_export_as_csv(): void
    {
        $csv = $this->actingAs($this->host)->get(route('host.properties.export'))->assertOk()->streamedContent();

        $this->assertStringStartsWith("Name,Address,\"WiFi SSID\",Guests,\"Access points\",Created\n\"Sunset Villa\"", $csv);
    }

    public function test_marketing_page_shows_real_suggestions_and_reach(): void
    {
        Guest::factory()->count(12)->create(['property_id' => $this->property->id, 'consented_at' => now(), 'marketing_opt_in' => false, 'phone' => '+254700000001']);
        Guest::factory()->count(3)->create(['property_id' => $this->property->id, 'consented_at' => now(), 'marketing_opt_in' => true, 'phone' => '+254700000002']);
        Campaign::create(['user_id' => $this->host->id, 'name' => 'Draft', 'type' => 'sms', 'status' => 'draft']);

        $this->actingAs($this->host)->get(route('host.marketing.index'))->assertInertia(fn ($page) => $page
            ->where('stats.reachable', 3)
            ->where('insights.0.text', 'Only 3 of 15 WiFi guests opted in to offers')
            ->where('insights.1.text', 'Welcome every new connection automatically')
            ->where('insights.2.text', 'Add your Google review link')
            ->where('insights.3.text', '1 draft campaign not sent yet'));
    }

    public function test_reach_estimate_matches_the_audience_and_stays_in_your_properties(): void
    {
        Guest::factory()->create(['property_id' => $this->property->id, 'phone' => '+254700000001', 'total_visits' => 1]);
        Guest::factory()->create(['property_id' => $this->property->id, 'phone' => '+254700000002', 'total_visits' => 3]);
        $other = Property::factory()->create();
        Guest::factory()->create(['property_id' => $other->id, 'phone' => '+254700000003']);

        $this->actingAs($this->host)->getJson(route('host.marketing.estimate', ['type' => 'sms']))->assertJson(['count' => 2]);
        $this->actingAs($this->host)->getJson(route('host.marketing.estimate', ['type' => 'sms', 'target_audience' => 'returning_guests']))->assertJson(['count' => 1]);
        $this->actingAs($this->host)->getJson(route('host.marketing.estimate', ['type' => 'sms', 'audience_property_id' => $other->id]))->assertJson(['count' => 0]);
    }

    public function test_guest_profile_edits_notes_and_shows_activity(): void
    {
        $guest = Guest::factory()->create(['property_id' => $this->property->id, 'first_name' => 'Wanjiru', 'phone' => '+254712345678',
            'source' => 'WiFi', 'review_requested_at' => now()->subDay(), 'review_clicked_at' => now()]);
        $campaign = Campaign::create(['user_id' => $this->host->id, 'name' => 'Low season', 'type' => 'whatsapp', 'status' => 'active']);
        CampaignRecipient::create(['campaign_id' => $campaign->id, 'guest_id' => $guest->id, 'clicked_at' => now()]);

        $this->actingAs($this->host)->put(route('host.guests.update', $guest), ['phone' => '0722 000 111', 'notes' => 'Prefers the top floor'])
            ->assertSessionHasNoErrors();
        $guest->refresh();
        $this->assertSame(['+254722000111', 'Prefers the top floor'], [$guest->phone, $guest->notes]);

        $this->actingAs($this->host)->get(route('host.guests.show', $guest))->assertInertia(function ($page) {
            $labels = collect($page->toArray()['props']['activity'])->pluck('label');
            $this->assertTrue($labels->contains('Campaign: Low season'));
            $this->assertTrue($labels->contains('Review request sent'));
            $this->assertTrue($labels->contains('First seen'));
        });
    }
}
