<?php

namespace App\Services;

use App\Models\Property;
use App\Services\Messaging\Messenger;
use App\Support\Brand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Urgent alerts to a property's owner: on the dashboard, and on WhatsApp
 * (SMS fallback), or by email when they have no phone number.
 */
class HostAlertService
{
    public function __construct(protected Messenger $messenger) {}

    public function send(Property $property, string $type, string $title, string $message): void
    {
        NotificationService::propertyAlert($property, $type, $title, $message);

        $host = $property->host;

        if ($host?->phone_number) {
            $this->messenger->send('whatsapp', $host->phone_number, Brand::name()." alert: {$message}");
        } elseif ($host?->email) {
            try {
                Mail::raw($message, fn ($mail) => $mail->to($host->email)->subject(Brand::name().": {$title}"));
            } catch (\Throwable $e) {
                Log::error('Host alert email failed: '.$e->getMessage(), ['property' => $property->id]);
            }
        }
    }
}
