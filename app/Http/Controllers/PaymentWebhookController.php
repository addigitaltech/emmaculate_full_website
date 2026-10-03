<?php

namespace App\Http\Controllers;

use App\Domain\Payments\PaymentGatewayException;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Payments\PaymentSettlementService;
use App\Models\AuditLog;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $gateway, PaymentGatewayManager $gateways, PaymentSettlementService $settlement)
    {
        if (! in_array($gateway, ['paystack', 'flutterwave'], true)) {
            return response()->json(['message' => 'Unsupported gateway.'], 404);
        }
        $provider = $gateways->resolve($gateway);
        $rawBody = $request->getContent();
        if (! $provider->authenticateWebhook($rawBody, $request->headers->all())) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }
        try {
            $payload = $provider->parseWebhook($rawBody);
        } catch (PaymentGatewayException) {
            return response()->json(['message' => 'Invalid event payload.'], 400);
        }
        $eventType = (string) ($payload['event'] ?? $payload['type'] ?? 'unknown');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $reference = (string) ($gateway === 'paystack'
            ? ($data['reference'] ?? $data['transaction_reference'] ?? data_get($data, 'transaction.reference', ''))
            : ($data['tx_ref'] ?? $data['reference'] ?? ''));
        $payloadHash = hash('sha256', $rawBody);
        $providerId = $data['id'] ?? $data['transaction_id'] ?? null;
        $eventIdentity = $providerId !== null ? (string) $providerId : $reference.'|'.$payloadHash;
        $eventId = $gateway.':'.substr(hash('sha256', $eventType.'|'.$eventIdentity), 0, 64);

        $event = PaymentEvent::query()->firstOrCreate(
            ['provider_event_id' => $eventId],
            ['gateway' => $gateway, 'event_type' => mb_substr($eventType, 0, 190), 'payload_hash' => $payloadHash, 'processing_status' => 'received'],
        );
        if (! $event->wasRecentlyCreated && in_array($event->processing_status, ['processed', 'ignored'], true)) {
            return response()->json(['received' => true]);
        }
        $transactionReference = $gateway === 'paystack'
            ? (string) ($data['transaction_reference'] ?? data_get($data, 'transaction.reference', $reference))
            : $reference;
        $transaction = $transactionReference !== '' ? PaymentTransaction::query()->where('reference', $transactionReference)->where('gateway', $gateway)->first() : null;
        if (! $transaction && $providerId !== null) {
            $transaction = PaymentTransaction::query()->where('gateway', $gateway)->where('provider_reference', (string) $providerId)->first();
        }
        if (! $transaction) {
            $event->update(['processing_status' => 'unmatched', 'failure_reason' => 'No local reference matched this provider event.', 'processed_at' => now()]);
            return response()->json(['received' => true]);
        }
        $event->update(['payment_transaction_id' => $transaction->id]);

        if ($gateway === 'paystack' && str_starts_with($eventType, 'refund.')) {
            return $this->handlePaystackRefundEvent($event, $transaction, $eventType, $data, $settlement);
        }

        $successEvent = ($gateway === 'paystack' && $eventType === 'charge.success')
            || ($gateway === 'flutterwave' && $eventType === 'charge.completed' && ($data['status'] ?? null) === 'successful');
        if (! $successEvent) {
            $event->update(['processing_status' => 'ignored', 'processed_at' => now()]);
            return response()->json(['received' => true]);
        }

        try {
            $providerReference = $gateway === 'flutterwave' ? (string) ($data['id'] ?? '') : (string) ($data['reference'] ?? '');
            $verification = $provider->verify($transaction, $providerReference);
            if (($verification['verified'] ?? false) !== true) {
                $event->update(['processing_status' => 'failed', 'failure_reason' => 'Provider verification did not match the local reference, status, amount or currency.', 'processed_at' => now()]);
                Log::warning('Payment webhook verification mismatch', ['gateway' => $gateway, 'reference_hash' => hash('sha256', $transactionReference), 'event_id' => $eventId]);
                return response()->json(['received' => true]);
            }
            $settlement->settleVerified($transaction, (string) ($verification['provider_reference'] ?? $providerReference));
            $event->update(['processing_status' => 'processed', 'failure_reason' => null, 'processed_at' => now()]);
            AuditLog::record(null, 'payments.webhook_processed', $transaction, ['gateway' => $gateway, 'event_type' => $eventType, 'event_id' => $eventId]);
            return response()->json(['received' => true]);
        } catch (Throwable $exception) {
            report($exception);
            $event->update(['processing_status' => 'failed', 'failure_reason' => 'Temporary processing error; provider may retry.', 'processed_at' => now()]);
            return response()->json(['message' => 'Temporary processing error.'], 500);
        }
    }

    private function handlePaystackRefundEvent(PaymentEvent $event, PaymentTransaction $transaction, string $eventType, array $data, PaymentSettlementService $settlement)
    {
        $target = match ($eventType) {
            'refund.pending', 'refund.processing' => 'accepted',
            'refund.needs-attention' => 'needs_attention',
            'refund.failed' => 'failed',
            'refund.processed' => 'completed',
            default => null,
        };
        if ($target === null) {
            $event->update(['processing_status' => 'ignored', 'processed_at' => now()]);
            return response()->json(['received' => true]);
        }

        try {
            DB::transaction(function () use ($event, $transaction, $eventType, $data, $target, $settlement): void {
                $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
                if ($locked->refund_status === 'completed' || ($locked->refund_status === 'failed' && $target !== 'completed')) {
                    $event->update(['processing_status' => 'ignored', 'processed_at' => now()]);
                    return;
                }
                if ($target === 'completed' && $locked->status === 'successful') {
                    if (! $settlement->markRefunded($locked)) {
                        $event->update(['processing_status' => 'ignored', 'failure_reason' => 'Refund completion did not match a successful transaction.', 'processed_at' => now()]);
                        return;
                    }
                } elseif ($target === 'completed' && $locked->status !== 'refunded') {
                    $event->update(['processing_status' => 'ignored', 'failure_reason' => 'Refund completion did not match a successful transaction.', 'processed_at' => now()]);
                    return;
                }
                $locked->update([
                    'refund_status' => $target,
                    'refund_provider_status' => substr((string) ($data['status'] ?? $eventType), 0, 80),
                    'refund_reference' => isset($data['refund_reference']) && $data['refund_reference'] !== '' ? substr((string) $data['refund_reference'], 0, 190) : $locked->refund_reference,
                    'refund_requested_at' => $locked->refund_requested_at ?? now(),
                ]);
                $event->update(['processing_status' => 'processed', 'failure_reason' => null, 'processed_at' => now()]);
                AuditLog::record(null, 'payments.refund_webhook_processed', $locked, ['gateway' => 'paystack', 'event_type' => $eventType, 'refund_status' => $target]);
            });
            return response()->json(['received' => true]);
        } catch (Throwable $exception) {
            report($exception);
            $event->update(['processing_status' => 'failed', 'failure_reason' => 'Temporary refund processing error; provider may retry.', 'processed_at' => now()]);
            return response()->json(['message' => 'Temporary processing error.'], 500);
        }
    }
}
