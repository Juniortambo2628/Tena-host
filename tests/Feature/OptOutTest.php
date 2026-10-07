<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use App\Services\CampaignAutomation;
use App\Services\CampaignDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OptOutTest extends TestCase
{
    use RefreshDatabase;

    private Guest $guest;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
            'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
            'verify_token' => 'verify-me', 'app_secret' => 'shh',
        ]]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABC']]])]);

        $host = User::factory()->create(['role' => 'host']);
        $property = Property::factory()->create(['user_id' => $host->id]);
        $this->guest = Guest::factory()->create(['property_id' => $property->id, 'phone' => '+254712345678',
            'consented_at' => now(), 'marketing_opt_in' => true]);
        $this->campaign = Campaign::create(['user_id' => $host->id, 'name' => 'Offer', 'type' => 'whatsapp', 'status' => 'draft',
            'content' => 'Hi!', 'trigger_event' => CampaignAutomation::CUSTOM_DATE]);
    }

    private function postSigned(array $payload)
    {
        $body = json_encode($payload);

        return $this->call('POST', route('whatsapp.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'shh'),
        ], $body);
    }

    private function reply(string $text): array
    {
        return ['entry' => [['changes' => [['value' => ['messages' => [['from' => '254712345678', 'type' => 'text', 'text' => ['body' => $text]]]]]]]]];
    }

    public function test_whatsapp_webhook_verification(): void
    {
        $this->get(route('whatsapp.webhook.verify', ['hub.mode' => 'subscribe', 'hub.verify_token' => 'verify-me', 'hub.challenge' => '42']))
            ->assertOk()->assertSee('42');
        $this->get(route('whatsapp.webhook.verify', ['hub.mode' => 'subscribe', 'hub.verify_token' => 'wrong', 'hub.challenge' => '42']))
            ->assertForbidden();
    }

    public function test_stop_opts_out_everywhere_and_start_opts_back_in(): void
    {
        $this->postSigned($this->reply('Stop'))->assertOk();

        $this->guest->refresh();
        $this->assertFalse($this->guest->marketing_opt_in);
        $this->assertNotNull($this->guest->opted_out_at);
        $this->assertSame(0, app(CampaignDispatcher::class)->audience($this->campaign)->count());
        Http::assertSent(fn (Request $r) => str_contains((string) ($r['text']['body'] ?? ''), "You won't get offers"));

        $this->postSigned($this->reply('START'));
        $this->assertTrue($this->guest->fresh()->marketing_opt_in);
        $this->assertSame(1, app(CampaignDispatcher::class)->audience($this->campaign)->count());
    }

    public function test_unsigned_whatsapp_posts_are_rejected(): void
    {
        $this->postJson(route('whatsapp.webhook'), $this->reply('STOP'))->assertForbidden();
        $this->assertTrue($this->guest->fresh()->marketing_opt_in);
    }

    public function test_sms_stop_reply(): void
    {
        $this->post(route('sms.inbound'), ['from' => '+254712345678', 'text' => 'STOP'])->assertOk();

        $this->assertNotNull($this->guest->fresh()->opted_out_at);
    }

    public function test_campaigns_carry_the_footer_and_read_receipts_count_as_opens(): void
    {
        app(CampaignAutomation::class)->activate($this->campaign);

        Http::assertSent(fn (Request $r) => ($r['text']['body'] ?? null) === "Hi!\n\nReply STOP to opt out.");
        $this->assertSame('wamid.ABC', CampaignRecipient::sole()->message_id);

        $this->postSigned(['entry' => [['changes' => [['value' => ['statuses' => [['id' => 'wamid.ABC', 'status' => 'read']]]]]]]]);

        $this->assertSame(1, $this->campaign->fresh()->total_opened);
    }
}
