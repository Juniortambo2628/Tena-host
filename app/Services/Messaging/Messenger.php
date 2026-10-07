<?php

namespace App\Services\Messaging;

use App\Models\Guest;
use App\Services\NullSmsDriver;
use App\Services\SmsDriverInterface;
use Illuminate\Support\Facades\Log;

/**
 * One way to send a text message to a phone, over SMS or WhatsApp.
 *
 * Each channel's driver is picked in config/services.php (SMS_DRIVER,
 * WHATSAPP_DRIVER). A WhatsApp message that can't be delivered falls back to
 * SMS, so guests still hear from the host when WhatsApp isn't set up yet.
 */
class Messenger
{
    public const CHANNELS = ['whatsapp', 'sms'];

    /** Short names usable in .env instead of class names. */
    private const DRIVERS = [
        'null' => NullSmsDriver::class,
        'africastalking' => AfricasTalkingSmsDriver::class,
        'whatsapp_cloud' => WhatsAppCloudDriver::class,
    ];

    /**
     * @return array{success: bool, channel: string, message?: string}
     */
    public function send(string $channel, string $to, string $message): array
    {
        if ($channel === 'whatsapp' && ! $this->configured('whatsapp')) {
            $channel = 'sms';
        }

        $result = $this->driver($channel)->send($to, $message);

        if (! $result['success'] && $channel === 'whatsapp' && config('services.whatsapp.fallback_to_sms', true)) {
            Log::info('WhatsApp undelivered, falling back to SMS', ['to' => $to, 'reason' => $result['message'] ?? null]);

            return $this->send('sms', $to, $message);
        }

        return $result + ['channel' => $channel];
    }

    /**
     * Send to a guest's phone, with %FIRSTNAME%-style placeholders filled in.
     *
     * @return array{success: bool, channel: string, message?: string}
     */
    public function toGuest(Guest $guest, string $channel, string $message): array
    {
        if (! $guest->phone) {
            return ['success' => false, 'channel' => $channel, 'message' => 'Guest has no phone number.'];
        }

        return $this->send($channel, $guest->phone, static::personalize($message, $guest));
    }

    /**
     * Fill guest placeholders. Both %FIRSTNAME% and {guest_name} styles work.
     */
    public static function personalize(string $content, Guest $guest): string
    {
        $property = $guest->property?->name ?? '';

        return strtr($content, [
            '%FIRSTNAME%' => $guest->first_name,
            '%LASTNAME%' => (string) $guest->last_name,
            '%EMAIL%' => (string) $guest->email,
            '%PROPERTY%' => $property,
            '{guest_name}' => $guest->first_name,
            '{property_name}' => $property,
        ]);
    }

    public function configured(string $channel): bool
    {
        return $this->driverName($channel) !== 'null';
    }

    public function driver(string $channel): SmsDriverInterface
    {
        $name = $this->driverName($channel);
        $class = self::DRIVERS[$name] ?? $name;

        return class_exists($class) ? app($class) : new NullSmsDriver;
    }

    private function driverName(string $channel): string
    {
        $name = config($channel === 'whatsapp' ? 'services.whatsapp.driver' : 'services.sms.driver') ?: 'null';

        return $name === NullSmsDriver::class ? 'null' : $name;
    }
}
