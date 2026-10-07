<?php

namespace App\Services\Billing;

use App\Models\Setting;
use App\Services\MpesaService;
use InvalidArgumentException;

/**
 * The single place TenaFi prices are worked out (config/billing.php).
 * Payments always charge a server-side quote, never an amount sent by the
 * browser.
 */
class PlanPricing
{
    /**
     * @return array{plan: string, plan_name: string, units: int, extra_devices: int, cycle: string, cycle_label: string, months: int, unit_price: int, volume_discount: int, extra_devices_total: int, monthly_total: int, cycle_discount: int, free_months: int, total: int, currency: string}
     */
    public function quote(string $plan, int $units = 1, int $extraDevices = 0, string $cycle = 'monthly'): array
    {
        $plans = config('billing.plans');
        $cycles = config('billing.cycles');

        if (! isset($plans[$plan])) {
            throw new InvalidArgumentException("Unknown plan [{$plan}].");
        }
        if (! isset($cycles[$cycle])) {
            throw new InvalidArgumentException("Unknown billing cycle [{$cycle}].");
        }

        $units = max(1, $units);
        $extraDevices = max(0, $extraDevices);
        $volumeDiscount = $this->volumeDiscount($units);
        $unitPrice = $plans[$plan]['price'];

        $devicesTotal = $extraDevices * config('billing.extra_device_price');
        $monthly = $unitPrice * $units * (100 - $volumeDiscount) / 100 + $devicesTotal;

        $c = $cycles[$cycle];
        $total = $monthly * ($c['months'] - $c['free_months']) * (100 - $c['discount']) / 100;

        return [
            'plan' => $plan,
            'plan_name' => $plans[$plan]['name'],
            'units' => $units,
            'extra_devices' => $extraDevices,
            'cycle' => $cycle,
            'cycle_label' => $c['label'],
            'months' => $c['months'],
            'unit_price' => $unitPrice,
            'volume_discount' => $volumeDiscount,
            'extra_devices_total' => $devicesTotal,
            'monthly_total' => (int) round($monthly),
            'cycle_discount' => $c['discount'],
            'free_months' => $c['free_months'],
            'total' => (int) round($total),
            'currency' => config('billing.currency'),
        ];
    }

    public function volumeDiscount(int $units): int
    {
        foreach (collect(config('billing.volume_discounts'))->sortKeysDesc() as $minUnits => $percent) {
            if ($units >= $minUnits) {
                return $percent;
            }
        }

        return 0;
    }

    /**
     * Validation rules for a plan selection. Optional rules suit query
     * params, where missing values fall back to the saved selection.
     *
     * @return array<string, string>
     */
    public static function rules(bool $optional = false): array
    {
        $rules = [
            'plan' => 'required|in:'.implode(',', array_keys(config('billing.plans'))),
            'units' => 'required|integer|min:1|max:1000',
            'extra_devices' => 'nullable|integer|min:0|max:100',
            'cycle' => 'required|in:'.implode(',', array_keys(config('billing.cycles'))),
        ];

        return $optional ? array_map(fn ($rule) => str_replace('required|', 'sometimes|', $rule), $rules) : $rules;
    }

    /**
     * Whether hosts must pay to use the dashboard. "auto" turns billing on
     * as soon as a payment provider (M-Pesa or Paystack) is configured.
     */
    public static function enforced(): bool
    {
        return match (Setting::getValue('billing_enabled', 'auto')) {
            'enabled' => true,
            'disabled' => false,
            default => MpesaService::fromSettings()->isConfigured() || (bool) config('services.paystack.public_key'),
        };
    }
}
