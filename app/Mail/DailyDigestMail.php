<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $digest
     * @param  array<int, array>  $alerts
     */
    public function __construct(
        public array $digest,
        public array $alerts = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ملخص نور القدس اليومي — '.$this->digest['date'],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.daily-digest');
    }
}
