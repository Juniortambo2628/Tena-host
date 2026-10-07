<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReviewRequestTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
            'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
        ]]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $host = User::factory()->create(['role' => 'host']);
        $this->property = Property::factory()->create([
            'user_id' => $host->id,
            'name' => 'Sunset Villa',
            'review_url' => 'https://g.page/r/sunset/review',
            'review_requests_enabled' => true,
            'review_request_delay_hours' => 24,
        ]);
    }

    private function guest(array $attributes = []): Guest
    {
        return Guest::factory()->create(array_merge([
            'property_id' => $this->property->id,
            'first_name' => 'Wanjiru',
            'phone' => '+254712345678',
            'consented_at' => now()->subDays(3),
            'last_connected' => now()->subDays(2),
            'check_out' => null,
        ], $attributes));
    }

    public function test_wifi_guest_is_thanked_once_after_the_delay(): void
    {
        $guest = $this->guest();

        $this->artisan('reviews:send')->expectsOutput('Review requests sent: 1')->assertSuccessful();
        $this->artisan('reviews:send')->expectsOutput('Review requests sent: 0');

        $guest->refresh();
        $this->assertNotNull($guest->review_requested_at);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => str_starts_with($r['text']['body'], 'Hi Wanjiru, thank you for staying at Sunset Villa!')
            && str_contains($r['text']['body'], url('/r/'.$guest->review_token)));
    }

    public function test_guests_still_around_or_long_gone_are_not_asked(): void
    {
        $this->guest(['last_connected' => now()->subHours(2)]);   // still here
        $this->guest(['last_connected' => now()->subDays(30)]);   // before the feature was on
        $this->guest(['check_out' => now()->addDay()->toDateString(), 'last_connected' => now()->subDays(2)]); // booking not over
        $this->guest(['consented_at' => null]);                    // no consent, no booking
        $this->guest(['phone' => null]);

        $this->artisan('reviews:send')->expectsOutput('Review requests sent: 0');
        Http::assertNothingSent();
    }

    public function test_pms_guest_is_asked_after_check_out(): void
    {
        $this->guest(['consented_at' => null, 'last_connected' => null, 'check_out' => now()->subDays(2)->toDateString()]);

        $this->artisan('reviews:send')->expectsOutput('Review requests sent: 1');
    }

    public function test_disabled_properties_are_skipped(): void
    {
        $this->property->update(['review_requests_enabled' => false]);
        $this->guest();

        $this->artisan('reviews:send')->expectsOutput('Review requests sent: 0');
    }

    public function test_custom_message_is_used(): void
    {
        $this->property->update(['review_message' => 'Asante {guest_name}! {review_link}']);
        $this->guest();

        $this->artisan('reviews:send');

        Http::assertSent(fn (Request $r) => str_starts_with($r['text']['body'], 'Asante Wanjiru! http'));
    }

    public function test_review_link_counts_the_click_and_redirects(): void
    {
        $guest = $this->guest();
        $this->artisan('reviews:send');
        $token = $guest->fresh()->review_token;

        $this->get('/r/'.$token)->assertRedirect('https://g.page/r/sunset/review');
        $this->assertNotNull($guest->fresh()->review_clicked_at);

        $this->get('/r/nope1234')->assertNotFound();
    }

    public function test_host_saves_review_settings(): void
    {
        $this->actingAs($this->property->host)
            ->patch(route('host.properties.update', $this->property), [
                'name' => 'Sunset Villa',
                'review_requests_enabled' => '1',
                'review_url' => 'https://g.page/r/new',
                'review_request_delay_hours' => 6,
                'review_message' => 'Thanks {guest_name}! {review_link}',
            ])
            ->assertSessionHasNoErrors();

        $this->property->refresh();
        $this->assertSame('https://g.page/r/new', $this->property->review_url);
        $this->assertSame(6, $this->property->review_request_delay_hours);
    }

    public function test_review_settings_are_validated(): void
    {
        $this->actingAs($this->property->host)
            ->patch(route('host.properties.update', $this->property), [
                'name' => 'Sunset Villa',
                'review_requests_enabled' => '1',
                'review_url' => '',
                'review_message' => 'Thanks!',
            ])
            ->assertSessionHasErrors(['review_url', 'review_message']);
    }
}
