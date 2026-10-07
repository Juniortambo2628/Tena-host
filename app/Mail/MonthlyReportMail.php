<?php

namespace App\Mail;

use App\Models\User;
use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $report  from MonthlyReportService::build
     */
    public function __construct(public User $user, public array $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your '.Brand::name()." report for {$this->report['month']}", tags: ['monthly-report']);
    }

    public function content(): Content
    {
        $people = $this->user->isBusiness() ? 'customers' : 'guests';

        return new Content(view: 'emails.monthly-report', with: [
            'heading' => "Your {$this->report['month']} report",
            'name' => $this->user->first_name,
            'rows' => [
                "New {$people} captured" => $this->report['new_guests'],
                "Returning {$people}" => $this->report['returning_guests'],
                'Campaign messages sent' => $this->report['messages_sent'],
                'Review requests sent' => $this->report['reviews_requested'],
                'Review links opened' => $this->report['reviews_opened'],
            ],
            'actionUrl' => route('dashboard'),
        ]);
    }
}
