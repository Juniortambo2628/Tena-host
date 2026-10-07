<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SignupAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Registration $registration) {}

    public static function summaryLine(Registration $registration): string
    {
        $name = trim("{$registration->first_name} {$registration->last_name}");
        $who = $registration->business_name ? "{$name} ({$registration->business_name})" : $name;
        $where = $registration->location ? " in {$registration->location}" : '';

        return 'New TenaFi '.($registration->type === Registration::TYPE_BUSINESS ? 'business' : 'host')." sign-up: {$who}{$where}";
    }

    public function envelope(): Envelope
    {
        $r = $this->registration;

        return new Envelope(
            subject: static::summaryLine($r),
            replyTo: $r->email ? [new Address($r->email, trim("{$r->first_name} {$r->last_name}"))] : [],
            tags: ['signup', $this->registration->type],
        );
    }

    public function content(): Content
    {
        $r = $this->registration;
        $rows = collect([
            'Type' => ucfirst($r->type),
            'Name' => trim("{$r->first_name} {$r->last_name}"),
            'Business' => $r->business_name,
            'Email' => $r->email,
            'Phone' => $r->phone,
            'Location' => $r->location,
            'Property type' => $r->property_type !== 'other' ? $r->property_type : null,
            'Units' => $r->units,
            'Main booking platform' => $r->primary_platform,
            'Biggest challenge' => $r->biggest_challenge,
            'Heard about us' => $r->referral_source,
        ])->merge(collect($r->answers ?? [])->mapWithKeys(fn ($v, $k) => [Str::headline($k) => is_array($v) ? implode(', ', $v) : $v]))
            ->filter(fn ($v) => filled($v));

        return new Content(view: 'emails.signup-alert', with: [
            'heading' => static::summaryLine($r),
            'rows' => $rows,
            'consentText' => $r->consent_text,
            'consentedAt' => $r->consented_at?->toDayDateTimeString(),
            'adminUrl' => route('admin.registrations.index', ['type' => $r->type]),
            'phoneLink' => $r->phone ? 'https://wa.me/'.preg_replace('/\D+/', '', $r->phone) : null,
        ]);
    }
}
