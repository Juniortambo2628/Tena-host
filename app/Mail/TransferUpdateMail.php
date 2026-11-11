<?php

namespace App\Mail;

use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * An email to an owner about a property transfer (PropertyTransferService):
 * offered, accepted (cooling off), completed or cancelled. A main button, and
 * for the old owner an "It wasn't me" cancel link that works without signing in.
 */
class TransferUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $lines
     * @param  array{label: string, url: string}|null  $button
     * @param  array{label: string, url: string}|null  $cancel
     */
    public function __construct(
        public string $heading,
        public array $lines,
        public ?array $button = null,
        public ?array $cancel = null,
        public ?string $name = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading.' - '.Brand::name());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.transfer-update', with: [
            'heading' => $this->heading,
            'lines' => $this->lines,
            'button' => $this->button,
            'cancel' => $this->cancel,
            'name' => $this->name,
        ]);
    }
}
