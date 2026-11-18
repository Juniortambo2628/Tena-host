<?php

namespace App\Services;

use App\Mail\SignupAlertMail;
use App\Mail\SignupConfirmationMail;
use App\Models\Registration;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Everything that happens after a new public sign-up is saved. Each channel
 * fails independently so one broken integration never blocks the others.
 */
class SignupAlertService
{
    public function notify(Registration $registration): void
    {
        $this->emailTeam($registration);
        $this->postWebhook($registration);
        $this->notifyAdmins($registration);
        $this->confirmApplicant($registration);
    }

    /**
     * @return array<int, string>
     */
    public static function recipients(): array
    {
        $configured = (string) Setting::getValue('signup_alert_emails', '');
        $fallback = Setting::getValue('support_email', config('mail.from.address'));

        return collect(preg_split('/[\s,;]+/', $configured ?: (string) $fallback))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    private function emailTeam(Registration $registration): void
    {
        $this->attempt('team email', function () use ($registration) {
            if ($recipients = static::recipients()) {
                Mail::to($recipients)->send(new SignupAlertMail($registration));
            }
        });
    }

    /**
     * Optional JSON webhook (Settings -> "Sign-up alert webhook"). Point it at
     * Zapier / Make / Twilio Studio to fan the alert out to WhatsApp.
     */
    private function postWebhook(Registration $registration): void
    {
        $url = (string) Setting::getValue('signup_alert_webhook_url', '');

        if (! $url) {
            return;
        }

        $this->attempt('webhook', fn () => Http::timeout(5)->post($url, [
            'event' => 'signup.created',
            'text' => SignupAlertMail::summaryLine($registration),
            'signup' => $registration->only([
                'id', 'type', 'first_name', 'last_name', 'business_name', 'email', 'phone',
                'location', 'source_page', 'answers', 'created_at',
            ]),
            'admin_url' => route('admin.registrations.index', ['type' => $registration->type]),
        ])->throw());
    }

    private function notifyAdmins(Registration $registration): void
    {
        $this->attempt('dashboard notification', fn () => NotificationService::signupReceived($registration));
    }

    private function confirmApplicant(Registration $registration): void
    {
        if (! $registration->email) {
            return; // Email is optional; the team follows up on WhatsApp.
        }

        // "Welcome to TenaFi." (SignupConfirmationMail), which replaced the old waitlist email.
        $this->attempt('applicant confirmation', fn () => Mail::to($registration->email)->send(new SignupConfirmationMail($registration)));
    }

    private function attempt(string $channel, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error("Sign-up alert ({$channel}) failed: ".$e->getMessage());
        }
    }
}
