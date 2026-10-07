<?php

namespace Tests\Feature;

use App\Mail\MonthlyReportMail;
use App\Models\Campaign;
use App\Models\Guest;
use App\Models\MarketingEvent;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\MonthlyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
            'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
        ]]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(8, 0));
        $this->host = User::factory()->create(['role' => 'host', 'email' => 'host@example.com', 'phone_number' => '+254700000001', 'billing_plan' => 'starter']);
        $property = Property::factory()->create(['user_id' => $this->host->id]);
        $september = now()->subMonthNoOverflow()->setDay(10);

        Guest::factory()->count(3)->create(['property_id' => $property->id, 'created_at' => $september, 'review_requested_at' => $september, 'review_clicked_at' => null]);
        Guest::factory()->create(['property_id' => $property->id, 'created_at' => $september, 'review_requested_at' => $september, 'review_clicked_at' => $september]);
        Guest::factory()->create(['property_id' => $property->id, 'created_at' => now()->subMonths(4), 'last_connected' => $september]);
        Guest::factory()->create(['property_id' => $property->id, 'created_at' => now()->subMonths(4), 'last_connected' => now()->subMonths(3)]);
        $campaign = Campaign::create(['user_id' => $this->host->id, 'name' => 'Sept', 'type' => 'whatsapp', 'status' => 'active']);
        MarketingEvent::create(['campaign_id' => $campaign->id, 'guest_id' => Guest::first()->id, 'event_type' => 'sent'])->forceFill(['created_at' => $september])->save();
    }

    public function test_report_counts_last_month(): void
    {
        $report = app(MonthlyReportService::class)->build($this->host, now()->subMonthNoOverflow());

        $this->assertSame([
            'month' => 'September 2026', 'places' => 1, 'new_guests' => 4, 'returning_guests' => 1,
            'messages_sent' => 1, 'reviews_requested' => 4, 'reviews_opened' => 1,
        ], $report);
    }

    public function test_command_emails_and_whatsapps_the_report(): void
    {
        $this->artisan('reports:monthly')->expectsOutput('Monthly reports sent: 1')->assertSuccessful();

        Mail::assertSent(MonthlyReportMail::class, fn ($mail) => $mail->hasTo('host@example.com')
            && $mail->envelope()->subject === 'Your TenaFi report for September 2026');
        Http::assertSent(fn (Request $r) => str_starts_with($r['text']['body'], 'TenaFi report for September 2026: 4 new guests, 1 returning'));
        $this->assertStringContainsString('New guests captured', (new MonthlyReportMail($this->host, app(MonthlyReportService::class)->build($this->host, now()->subMonthNoOverflow())))->render());
    }

    public function test_only_starter_and_growth_get_it_once_billing_is_live(): void
    {
        Setting::setValue('billing_enabled', 'enabled', 'billing', 'string');

        $this->assertCount(0, app(MonthlyReportService::class)->recipients()); // no active subscription yet

        $this->host->subscriptions()->create(['type' => 'default', 'stripe_id' => 'x', 'stripe_status' => 'active', 'ends_at' => now()->addMonth()]);
        $this->assertCount(1, app(MonthlyReportService::class)->recipients());

        $this->host->update(['billing_plan' => 'basic']);
        $this->assertCount(0, app(MonthlyReportService::class)->recipients());
    }
}
