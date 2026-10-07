<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

/**
 * TenaFi's M-Pesa paybill (host subscriptions and guest extras) and the
 * payouts owed to hosts for extras guests paid on it.
 */
class MpesaSettingsController extends Controller
{
    private const GROUP = 'mpesa';

    public function edit()
    {
        $current = MpesaService::settings();

        return Inertia::render('Admin/Settings/Mpesa', [
            'settings' => [
                'env' => $current['env'],
                'account_type' => $current['account_type'],
                'shortcode' => $current['shortcode'],
                'consumer_key' => $current['key'] ?? '',
                // Secrets never go to the browser, only whether one is stored.
                'has_secret' => filled($current['secret']),
                'has_custom_passkey' => $current['passkey'] !== MpesaService::SANDBOX_PASSKEY,
                'extras_enabled' => (bool) Setting::getValue('mpesa_extras_enabled', true),
                'extras_fee_percent' => (float) Setting::getValue('mpesa_extras_fee_percent', 0),
            ],
            'configured' => MpesaService::fromSettings()->isConfigured(),
            'isPlaceholder' => $current['shortcode'] === MpesaService::SANDBOX_SHORTCODE,
            'callbacks' => [
                'subscriptions' => route('mpesa.callback'),
                'extras' => route('mpesa.extras.callback'),
            ],
            'payouts' => $this->payouts(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'env' => 'required|in:sandbox,production',
            'account_type' => 'required|in:paybill,till',
            'shortcode' => 'required|digits_between:5,7',
            'consumer_key' => 'nullable|string|max:255',
            'consumer_secret' => 'nullable|string|max:255',
            'passkey' => 'nullable|string|max:255',
            'extras_enabled' => 'boolean',
            'extras_fee_percent' => 'numeric|min:0|max:50',
        ]);

        Setting::setValue('mpesa_env', $validated['env'], self::GROUP);
        Setting::setValue('mpesa_account_type', $validated['account_type'], self::GROUP);
        Setting::setValue('mpesa_shortcode', $validated['shortcode'], self::GROUP);
        Setting::setValue('mpesa_consumer_key', (string) ($validated['consumer_key'] ?? ''), self::GROUP);
        Setting::setValue('mpesa_extras_enabled', $validated['extras_enabled'] ? '1' : '0', self::GROUP, 'boolean');
        Setting::setValue('mpesa_extras_fee_percent', (string) $validated['extras_fee_percent'], self::GROUP);

        // Secrets: only replaced when a new value is typed; stored encrypted.
        foreach (['consumer_secret' => 'mpesa_consumer_secret', 'passkey' => 'mpesa_passkey'] as $field => $key) {
            if (filled($validated[$field] ?? null)) {
                Setting::setValue($key, Crypt::encryptString($validated[$field]), self::GROUP);
            }
        }

        return back()->with('success', 'M-Pesa settings saved.');
    }

    /**
     * Mark every paid, unsettled extras order of a host as paid out.
     */
    public function settle(User $user)
    {
        $count = Order::forHost($user)->where('payment_status', 'paid')->whereNull('settled_at')->update(['settled_at' => now()]);

        return back()->with('success', "Marked {$count} order(s) for {$user->first_name} as paid out.");
    }

    /**
     * What each host is owed for paid extras not yet settled.
     *
     * @return list<array<string, mixed>>
     */
    protected function payouts(): array
    {
        return Order::with('property.host')
            ->where('payment_status', 'paid')->whereNull('settled_at')
            ->get()
            ->groupBy(fn (Order $o) => $o->property?->user_id)
            ->filter(fn ($orders, $hostId) => $hostId)
            ->map(function ($orders) {
                $host = $orders->first()->property->host;

                return [
                    'host_id' => $host->id,
                    'host' => trim("{$host->first_name} {$host->last_name}"),
                    'phone' => $host->phone_number,
                    'orders' => $orders->count(),
                    'gross' => round($orders->sum('total'), 2),
                    'fee' => round($orders->sum('platform_fee'), 2),
                    'owed' => round($orders->sum(fn (Order $o) => $o->host_payout), 2),
                ];
            })
            ->sortByDesc('owed')->values()->all();
    }
}
