<?php

namespace App\Mail;

use App\Models\PaymentTransaction;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent to the person who paid: confirmation with the receipt attached. */
class PaymentReceivedMail extends Mailable
{
    public function __construct(public PaymentTransaction $transaction, public array $balance, public ?string $receiptPdf = null, public ?string $receiptUrl = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment received - receipt '.($this->transaction->receipt?->receipt_number ?? ''));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-received');
    }

    public function attachments(): array
    {
        if (! $this->receiptPdf) {
            return [];
        }
        $pdf = $this->receiptPdf;

        return [Attachment::fromData(fn () => $pdf, 'receipt-'.($this->transaction->receipt?->receipt_number ?? 'payment').'.pdf')->withMime('application/pdf')];
    }
}
