<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?string $name = null,
        public ?string $role = null,
        public ?string $actionUrl = null,
        public ?string $invitedBy = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join '.Setting::getValue('site_name', 'Tena'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.user-invitation', [
                'name' => $this->name,
                'role' => $this->role,
                'actionUrl' => $this->actionUrl,
                'invitedBy' => $this->invitedBy,
            ])->render(),
        );
    }
}
