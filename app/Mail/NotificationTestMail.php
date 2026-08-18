<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $requestedBy,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PayEase Notification Settings Test',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification-test',
            with: [
                'requestedBy' => $this->requestedBy,
                'sentAt' => now(),
            ],
        );
    }
}
