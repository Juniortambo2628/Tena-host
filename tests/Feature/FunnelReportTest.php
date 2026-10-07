<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\Registration;
use App\Models\User;
use App\Services\Analytics\FunnelReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunnelReportTest extends TestCase
{
    use RefreshDatabase;

    private function metric(string $name, int $value, $date = null): void
    {
        Analytics::create(['metric_name' => $name, 'metric_value' => $value, 'date_recorded' => $date ?? today()]);
    }

    public function test_funnel_joins_tracked_events_with_signups_accounts_and_payments(): void
    {
        $this->metric('public.path_card_hosts:home', 40);
        $this->metric('public.join_click:hosts', 20);
        $this->metric('public.join_click:hosts', 5, today()->subDays(60)); // outside the window
        $this->metric('public.signup_step_1:hosts', 12);
        $this->metric('public.signup_step_2:hosts', 8);
        $this->metric('public.join_click:business', 3);

        $paying = User::factory()->create(['role' => 'host', 'account_type' => 'host', 'billing_plan' => 'starter']);
        $paying->subscriptions()->create(['type' => 'default', 'stripe_id' => 'x', 'stripe_status' => 'active', 'ends_at' => now()->addMonth()]);
        $unpaid = User::factory()->create(['role' => 'host']);
        Registration::factory()->create(['type' => 'host', 'user_id' => $paying->id]);
        Registration::factory()->create(['type' => 'host', 'user_id' => $unpaid->id]);
        Registration::factory()->create(['type' => 'host']);
        Registration::factory()->create(['type' => 'business']);

        $funnels = app(FunnelReport::class)->funnels();

        $this->assertSame([40, 20, 12, 8, 3, 2, 1], array_column($funnels['host'], 'value'));
        $this->assertSame([0, 3, 0, 0, 1, 0, 0], array_column($funnels['business'], 'value'));
    }

    public function test_signups_by_type_and_plan_mix(): void
    {
        Registration::factory()->count(2)->create(['type' => 'host']);
        Registration::factory()->create(['type' => 'business']);
        Registration::factory()->create(['type' => 'host', 'created_at' => now()->subMonths(2)]);

        $byType = app(FunnelReport::class)->signupsByType();
        $this->assertCount(6, $byType);
        $this->assertSame(['name' => now()->format('M'), 'host' => 2, 'business' => 1], end($byType));

        $user = User::factory()->create(['billing_plan' => 'growth']);
        $user->subscriptions()->create(['type' => 'default', 'stripe_id' => 'y', 'stripe_status' => 'active', 'ends_at' => now()->addMonth()]);
        User::factory()->create(['billing_plan' => 'basic']); // never paid

        $this->assertSame([['name' => 'Growth', 'value' => 1]], app(FunnelReport::class)->planMix());
    }

    public function test_admin_dashboard_includes_the_funnel(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('analytics.funnels.host', 7)
                ->has('analytics.funnels.business', 7)
                ->has('analytics.signupsByType', 6)
                ->has('analytics.revenue', 6));
    }
}
