<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowFloatAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $agentName,
        public string $floatBalance,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Low Float Balance Alert - PayEase');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.low-float-alert');
    }
}
