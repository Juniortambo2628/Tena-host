<?php

namespace App\Mail;

use App\Models\Guest;
use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "{Venue} has a new owner": the note to a transferred property's guests
 * (TransferNoticeService), in the venue's name, logo and colours, like the
 * review request email, with its unsubscribe link.
 */
class TransferNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Guest $guest, public string $newOwner) {}

    public function envelope(): Envelope
    {
        $property = $this->guest->property;

        return new Envelope(
            from: new Address((string) config('mail.from.address'), "{$property->name} via ".Brand::name()),
            subject: "{$property->name} has a new owner",
        );
    }

    public function content(): Content
    {
        $property = $this->guest->property;
        // The venue's look and the unsubscribe link, as in the review request email.
        $data = ReviewRequestMail::viewData($this->guest, '');

        return new Content(view: 'emails.transfer-notice', with: [
            'theme' => $data['theme'],
            'logoUrl' => $data['logoUrl'],
            'accent' => $data['accent'],
            'business' => $data['business'],
            'unsubscribeUrl' => $data['unsubscribeUrl'],
            'propertyName' => $property->name,
            'subject' => "{$property->name} has a new owner",
            'greeting' => 'Hi '.($this->guest->first_name ?: 'there').',',
            'body' => "A quick note from {$property->name}: it's now run by {$this->newOwner}. They'll look after the details you shared with us and may send you {$property->name}'s news and offers, as before.",
            'choice' => 'If you’d rather not hear from us, you can unsubscribe below at any time.',
        ]);
    }
}
