<?php

namespace App\Mail;

use App\Models\SchoolSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** The one email design used for news, events, admission replies and messages sent from the admin. */
class SchoolUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subjectLine,
        public string $heading,
        public string $bodyText,
        public ?string $url = null,
        public ?string $buttonLabel = null,
        public ?string $imageUrl = null,
        public ?string $unsubscribeUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        $settings = SchoolSettings::current();

        return new Content(view: 'emails.school-update', with: [
            'schoolName' => $settings->school_name,
            'logoUrl' => $settings->logo_path ? asset('storage/'.$settings->logo_path) : null,
            'address' => $settings->address,
        ]);
    }
}
