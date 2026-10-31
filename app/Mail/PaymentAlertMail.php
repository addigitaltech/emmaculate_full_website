<?php

namespace App\Mail;

use App\Models\PaymentTransaction;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent to the school (finance) every time a payment is verified. */
class PaymentAlertMail extends Mailable
{
    public function __construct(public PaymentTransaction $transaction, public array $balance, public ?string $historyUrl = null)
    {
    }

    public function envelope(): Envelope
    {
        $student = $this->transaction->student;

        return new Envelope(subject: 'New payment: NGN '.number_format((float) $this->transaction->amount, 2).' for '.($student?->fullName() ?? 'a student'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-alert');
    }
}
