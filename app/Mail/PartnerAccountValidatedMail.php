<?php

namespace App\Mail;

use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerAccountValidatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Partner $partner
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte partenaire est activé – ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-account-validated',
        );
    }
}
