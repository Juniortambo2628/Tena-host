<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use App\Services\CampaignDispatcher;
use App\Services\Messaging\Messenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private function useAfricasTalking(): void
    {
        config([
            'services.sms.driver' => 'africastalking',
            'services.africastalking' => ['username' => 'tenafi', 'api_key' => 'at-key', 'from' => 'TENAFI'],
        ]);
    }

    private function useWhatsApp(?string $template = 'guest_update'): void
    {
        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 'wa-token', 'phone_number_id' => '12345',
            'template' => $template, 'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => true,
        ]]);
    }

    private function atAccepts(): array
    {
        return ['SMSMessageData' => ['Message' => 'Sent to 1/1', 'Recipients' => [
            ['statusCode' => 101, 'number' => '+254712345678', 'status' => 'Success', 'messageId' => 'ATXid_1'],
        ]]];
    }

    private function guest(array $attributes = []): Guest
    {
        $host = User::factory()->create(['role' => 'host']);
        $property = Property::factory()->create(['user_id' => $host->id, 'name' => 'Sunset Villa']);

        return Guest::factory()->create(array_merge([
            'property_id' => $property->id, 'first_name' => 'Wanjiru', 'phone' => '+254712345678',
        ], $attributes));
    }

    public function test_africas_talking_sends_sms(): void
    {
        $this->useAfricasTalking();
        Http::fake(['api.africastalking.com/*' => Http::response($this->atAccepts(), 201)]);

        $result = app(Messenger::class)->send('sms', '+254712345678', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('sms', $result['channel']);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.africastalking.com/version1/messaging'
            && $r->hasHeader('apiKey', 'at-key')
            && $r['username'] === 'tenafi' && $r['to'] === '+254712345678' && $r['from'] === 'TENAFI');
    }

    public function test_africas_talking_rejection_is_a_failure(): void
    {
        $this->useAfricasTalking();
        Http::fake(['*' => Http::response(['SMSMessageData' => ['Message' => 'Sent to 0/1', 'Recipients' => [
            ['statusCode' => 405, 'status' => 'UnknownError'],
        ]]], 201)]);

        $result = app(Messenger::class)->send('sms', '+254712345678', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertSame('UnknownError', $result['message']);
    }

    public function test_whatsapp_sends_template_with_message_as_variable(): void
    {
        $this->useWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $result = app(Messenger::class)->send('whatsapp', '+254712345678', 'Karibu!');

        $this->assertTrue($result['success']);
        $this->assertSame('whatsapp', $result['channel']);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://graph.facebook.com/v21.0/12345/messages'
            && $r->hasHeader('Authorization', 'Bearer wa-token')
            && $r['to'] === '254712345678'
            && $r['template']['name'] === 'guest_update'
            && $r['template']['components'][0]['parameters'][0]['text'] === 'Karibu!');
    }

    public function test_whatsapp_without_template_sends_text(): void
    {
        $this->useWhatsApp(template: null);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.2']]])]);

        app(Messenger::class)->send('whatsapp', '+254712345678', 'Karibu!');

        Http::assertSent(fn (Request $r) => $r['type'] === 'text' && $r['text']['body'] === 'Karibu!');
    }

    public function test_failed_whatsapp_falls_back_to_sms(): void
    {
        $this->useWhatsApp();
        $this->useAfricasTalking();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Recipient not on WhatsApp']], 400),
            'api.africastalking.com/*' => Http::response($this->atAccepts(), 201),
        ]);

        $result = app(Messenger::class)->send('whatsapp', '+254712345678', 'Karibu!');

        $this->assertTrue($result['success']);
        $this->assertSame('sms', $result['channel']);
    }

    public function test_unconfigured_whatsapp_goes_straight_to_sms(): void
    {
        $this->useAfricasTalking();
        Http::fake(['api.africastalking.com/*' => Http::response($this->atAccepts(), 201)]);

        $result = app(Messenger::class)->send('whatsapp', '+254712345678', 'Karibu!');

        $this->assertSame('sms', $result['channel']);
        Http::assertSentCount(1);
    }

    public function test_messages_to_guests_are_personalised(): void
    {
        $guest = $this->guest();

        $this->assertSame(
            'Hi Wanjiru, welcome to Sunset Villa. Hi Wanjiru!',
            Messenger::personalize('Hi {guest_name}, welcome to {property_name}. Hi %FIRSTNAME%!', $guest),
        );
    }

    public function test_whatsapp_campaign_is_delivered(): void
    {
        $this->useWhatsApp();
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.3']]])]);
        $guest = $this->guest();
        $campaign = Campaign::create([
            'user_id' => $guest->property->user_id, 'name' => 'Welcome', 'type' => 'whatsapp',
            'content' => 'Karibu {guest_name}!', 'status' => 'active',
        ]);

        SendCampaignJob::dispatchSync($campaign, $guest);

        $this->assertSame(1, $campaign->fresh()->total_sent);
        Http::assertSent(fn (Request $r) => $r['template']['components'][0]['parameters'][0]['text'] === 'Karibu Wanjiru!');
    }

    public function test_campaign_audience_respects_marketing_opt_in(): void
    {
        $optedIn = $this->guest(['consented_at' => now(), 'marketing_opt_in' => true]);
        $propertyId = $optedIn->property_id;
        Guest::factory()->create(['property_id' => $propertyId, 'consented_at' => now(), 'marketing_opt_in' => false]);
        $manual = Guest::factory()->create(['property_id' => $propertyId, 'consented_at' => null, 'phone' => '+254700000009']);

        $campaign = new Campaign(['user_id' => $optedIn->property->user_id, 'property_id' => $propertyId, 'type' => 'sms']);

        $this->assertEqualsCanonicalizing(
            [$optedIn->id, $manual->id],
            app(CampaignDispatcher::class)->audience($campaign)->pluck('id')->all(),
        );
    }
}
