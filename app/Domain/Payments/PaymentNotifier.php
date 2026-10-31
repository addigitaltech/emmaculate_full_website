<?php

namespace App\Domain\Payments;

use App\Domain\Notifications\SmsService;
use App\Mail\PaymentAlertMail;
use App\Mail\PaymentReceivedMail;
use App\Models\PaymentTransaction;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * When a payment is verified: email the person who paid (receipt attached), email the school's finance contacts and
 * text them (when SMS is set up). Each message is tried on its own, so one failure never blocks the others.
 */
final class PaymentNotifier
{
    public function paymentSettled(int $transactionId): void
    {
        // Claim the notification so it is only ever sent once, even if the provider calls us twice.
        $claimed = PaymentTransaction::query()->whereKey($transactionId)->whereNull('notified_at')->update(['notified_at' => now()]);
        if (! $claimed) {
            return;
        }

        $transaction = PaymentTransaction::query()->with(['student.schoolClass', 'student.arm', 'student.parents', 'initiator', 'receipt'])->findOrFail($transactionId);
        $settings = SchoolSettings::current();
        $balance = FeeBalance::forStudent((int) $transaction->student_id);

        $this->emailPayer($transaction, $balance);
        $this->emailSchool($transaction, $settings, $balance);
        $this->textSchoolAndPayer($transaction, $settings, $balance);
    }

    private function emailPayer(PaymentTransaction $transaction, array $balance): void
    {
        // The person who paid is told; when a student paid, the student's parents are told instead (students have no real email).
        $emails = ($transaction->initiator && ! $transaction->initiator->hasRole('Student'))
            ? collect([$transaction->initiator->email])
            : collect($transaction->student?->parents?->pluck('email') ?? []);
        $emails = $emails
            ->filter(fn ($email) => filled($email) && ! str_ends_with(strtolower((string) $email), '.invalid') && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => strtolower(trim((string) $email)))->unique()->values();
        if ($emails->isEmpty() || ! $transaction->receipt) {
            return;
        }

        try {
            $pdf = app(ReceiptService::class)->pdf($transaction->receipt);
        } catch (\Throwable $exception) {
            report($exception);
            $pdf = null;
        }
        $url = route('receipts.show', ['receipt' => $transaction->receipt->receipt_number]);
        foreach ($emails as $email) {
            $this->attempt(fn () => Mail::to($email)->send(new PaymentReceivedMail($transaction, $balance, $pdf, $url)));
        }
    }

    private function emailSchool(PaymentTransaction $transaction, SchoolSettings $settings, array $balance): void
    {
        $configured = preg_split('/[\s,;]+/', (string) $settings->finance_notify_emails, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $emails = collect($configured ?: [$settings->email])
            ->merge(User::role('Finance Admin')->pluck('email'))
            ->filter(fn ($email) => filled($email) && ! str_ends_with(strtolower((string) $email), '.invalid') && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => strtolower(trim((string) $email)))->unique()->values();
        if ($emails->isEmpty()) {
            return;
        }

        $url = route('finance.history');
        foreach ($emails as $email) {
            $this->attempt(fn () => Mail::to($email)->send(new PaymentAlertMail($transaction, $balance, $url)));
        }
    }

    private function textSchoolAndPayer(PaymentTransaction $transaction, SchoolSettings $settings, array $balance): void
    {
        if (! SmsService::enabled()) {
            return;
        }
        $sms = app(SmsService::class);
        $student = $transaction->student;
        $amount = 'NGN '.number_format((float) $transaction->amount, 2);
        $owing = 'NGN '.number_format((float) $balance['balance'], 2);

        $schoolText = $settings->short_name.': '.$amount.' received for '.$student?->fullName().' ('.$student?->student_number.'). Still owing '.$owing.'. Ref '.$transaction->reference;
        $phones = preg_split('/[\s,;]+/', (string) ($settings->finance_notify_phones ?: $settings->phone_primary), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach (collect($phones)->unique() as $phone) {
            $this->attempt(fn () => $sms->send($phone, $schoolText));
        }

        if ($settings->sms_payer) {
            $payerText = $settings->short_name.': payment of '.$amount.' for '.$student?->fullName().' received. Receipt '.($transaction->receipt?->receipt_number ?? '').'. Balance '.$owing.'. Thank you.';
            foreach (collect($student?->parents ?? [])->pluck('phone')->filter()->unique() as $phone) {
                $this->attempt(fn () => $sms->send((string) $phone, $payerText));
            }
        }
    }

    private function attempt(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
