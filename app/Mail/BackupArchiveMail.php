<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BackupArchiveMail extends Mailable
{
    public function __construct(public string $termLabel, public string $resultsPath, public string $peoplePath)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Emmaculate Academy backup — '.$this->termLabel);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.backup-archive');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->resultsPath)->as('emmaculate-results-backup.zip')->withMime('application/zip'),
            Attachment::fromPath($this->peoplePath)->as('emmaculate-people-backup.zip')->withMime('application/zip'),
        ];
    }
}
