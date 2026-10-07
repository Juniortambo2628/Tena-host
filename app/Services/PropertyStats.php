<?php

namespace App\Services;

use App\Models\AccessPoint;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Headline numbers for a host's properties, from real data.
 */
class PropertyStats
{
    public const OCCUPANCY_DAYS = 30;

    /**
     * @return array{occupancy: ?int, aps_online: int, aps_total: int}
     */
    public function forHost(User $user): array
    {
        $propertyIds = $user->properties()->pluck('id');
        $aps = AccessPoint::whereIn('property_id', $propertyIds);

        return [
            'occupancy' => $this->occupancy($propertyIds->all()),
            'aps_online' => (clone $aps)->where('status', 'online')->count(),
            'aps_total' => $aps->count(),
        ];
    }

    /**
     * Booked nights over the last 30 days as a % of available nights, from
     * PMS bookings. Null when there's no booking data to go on.
     *
     * @param  list<int>  $propertyIds
     */
    public function occupancy(array $propertyIds): ?int
    {
        if (! $propertyIds) {
            return null;
        }

        $from = now()->subDays(self::OCCUPANCY_DAYS)->startOfDay();
        $to = now()->startOfDay();

        $bookings = Guest::whereIn('property_id', $propertyIds)
            ->whereNotNull('check_in')->whereNotNull('check_out')
            ->whereDate('check_in', '<', $to)->whereDate('check_out', '>', $from)
            ->get(['property_id', 'check_in', 'check_out']);

        if ($bookings->isEmpty()) {
            return null;
        }

        $nights = $bookings->sum(function (Guest $b) use ($from, $to) {
            $start = Carbon::parse($b->check_in)->max($from);
            $end = Carbon::parse($b->check_out)->min($to);

            return max(0, (int) $start->diffInDays($end));
        });

        $available = count($propertyIds) * self::OCCUPANCY_DAYS;

        return (int) min(100, round($nights / $available * 100));
    }
}
