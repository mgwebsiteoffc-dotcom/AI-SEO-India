<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $digest;

    public function __construct(array $digest)
    {
        $this->digest = $digest;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AI Visibility Report — '.($this->digest['brand'] ?? 'your store').' ('.($this->digest['period'] ?? '').')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.weekly-report');
    }
}
