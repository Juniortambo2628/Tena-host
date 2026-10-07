<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Guest;
use App\Models\User;

/**
 * Suggestions on the marketing page, worked out from the host's own data.
 */
class MarketingInsights
{
    /**
     * @return list<array{text: string, detail: string, href: ?string}>
     */
    public function forHost(User $user): array
    {
        $propertyIds = $user->properties()->pluck('id');
        $guests = Guest::whereIn('property_id', $propertyIds);
        $campaigns = Campaign::where('user_id', $user->id);
        $people = $user->isBusiness() ? 'customers' : 'guests';
        $person = $user->isBusiness() ? 'customer' : 'guest';
        $tips = [];

        $consented = (clone $guests)->whereNotNull('consented_at')->count();
        $optedIn = (clone $guests)->whereNotNull('consented_at')->where('marketing_opt_in', true)->count();
        if ($consented >= 10 && $optedIn / $consented < 0.5) {
            $tips[] = [
                'text' => "Only {$optedIn} of {$consented} WiFi {$people} opted in to offers",
                'detail' => 'Mention a real perk on your WiFi page (a discount, late checkout) so more people tick the box.',
                'href' => route('host.properties.index'),
            ];
        }

        if (! (clone $campaigns)->where('status', 'active')->where('trigger_event', CampaignAutomation::CONNECTS)->exists()) {
            $tips[] = [
                'text' => 'Welcome every new connection automatically',
                'detail' => "Create a campaign triggered by \"Guest Connects to WiFi\" so each new {$person} hears from you.",
                'href' => route('host.marketing.builder'),
            ];
        }

        if ($user->properties()->whereNull('review_url')->exists()) {
            $tips[] = [
                'text' => 'Add your Google review link',
                'detail' => "Thank-you messages can't ask for reviews until a property has its review link.",
                'href' => route('host.properties.index'),
            ];
        }

        if ($drafts = (clone $campaigns)->where('status', 'draft')->count()) {
            $tips[] = [
                'text' => "{$drafts} draft campaign".($drafts > 1 ? 's' : '').' not sent yet',
                'detail' => 'Activate them or delete them to keep your list tidy.',
                'href' => null,
            ];
        }

        $best = (clone $campaigns)->where('total_sent', '>=', 20)->get()
            ->groupBy('type')->map(fn ($c) => $c->sum('total_clicked') / max(1, $c->sum('total_sent')));
        if ($best->count() > 1) {
            $channel = $best->sortDesc()->keys()->first();
            $tips[] = [
                'text' => ucfirst($channel).' gets the most clicks',
                'detail' => round($best[$channel] * 100, 1).'% of '.$channel.' messages are clicked. Lead with it.',
                'href' => null,
            ];
        }

        return $tips;
    }
}
