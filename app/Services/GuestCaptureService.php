<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Property;
use App\Support\Phone;

/**
 * Turns a WiFi login into a guest record the host owns. A guest is matched
 * per property by phone, then email, then device, so repeat visits update
 * one record instead of creating duplicates.
 */
class GuestCaptureService
{
    /** Connections closer together than this count as the same visit. */
    private const VISIT_GAP_HOURS = 6;

    /**
     * @param  array{first_name: string, phone: string, email?: ?string, marketing_opt_in?: bool}  $data
     */
    public function capture(Property $property, array $data, string $consentText, ?string $deviceMac = null): Guest
    {
        $phone = Phone::toE164($data['phone']);
        $email = filled($data['email'] ?? null) ? strtolower(trim($data['email'])) : null;

        $guest = Guest::where('property_id', $property->id)
            ->where(fn ($q) => $q->where('phone', $phone)
                ->when($email, fn ($q) => $q->orWhere('email', $email)))
            ->first() ?? new Guest(['property_id' => $property->id, 'source' => 'WiFi', 'total_visits' => 0]);

        $isNew = ! $guest->exists;

        $guest->fill([
            'first_name' => trim($data['first_name']),
            'phone' => $phone,
            'email' => $email ?? $guest->email,
            // Opting in sticks; leaving the box unticked later doesn't withdraw it.
            'marketing_opt_in' => $guest->marketing_opt_in || ! empty($data['marketing_opt_in']),
            'consent_text' => $consentText,
            'consented_at' => now(),
            'birthday' => filled($data['birthday_month'] ?? null)
                ? sprintf('%02d-%02d', $data['birthday_month'], $data['birthday_day'])
                : $guest->birthday,
        ]);

        $this->recordVisit($guest, $deviceMac);

        if ($isNew) {
            NotificationService::guestConnected($property->name);
        }

        return $guest;
    }

    /**
     * A returning device on the same property: count the visit, no form.
     */
    public function findReturning(Property $property, ?string $deviceMac): ?Guest
    {
        if (! $deviceMac) {
            return null;
        }

        return Guest::where('property_id', $property->id)->where('device_mac', $deviceMac)->latest('last_connected')->first();
    }

    public function recordVisit(Guest $guest, ?string $deviceMac = null): void
    {
        $isNewVisit = ! $guest->last_connected || $guest->last_connected->lt(now()->subHours(self::VISIT_GAP_HOURS));

        if ($isNewVisit) {
            $guest->total_visits = ($guest->total_visits ?? 0) + 1;
        }

        $guest->last_connected = now();
        $guest->device_mac = $deviceMac ?? $guest->device_mac;
        $guest->save();
    }
}
