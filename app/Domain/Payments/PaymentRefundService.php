<?php

namespace App\Domain\Payments;

use App\Models\AuditLog;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PaymentRefundService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentSettlementService $settlement,
    ) {}

    public function requestFullRefund(PaymentTransaction $transaction, User $actor): PaymentTransaction
    {
        if (! $actor->hasRole('Super Admin') && ! $actor->can('issue refunds')) {
            throw new AuthorizationException('You are not authorized to request refunds.');
        }
        $config = PaymentGatewayConfig::query()->where('driver', $transaction->gateway)->first();
        if (! $config || ! $config->is_enabled || ! $config->supports_refunds) {
            throw ValidationException::withMessages(['refund' => 'This provider is not configured for refunds.']);
        }
        $provider = $this->gateways->resolve($transaction->gateway);
        if (! $provider->supportsRefunds() || ! $this->isConfigured($transaction->gateway)) {
            throw ValidationException::withMessages(['refund' => 'This provider is not configured for refunds.']);
        }

        $claimed = DB::transaction(function () use ($transaction, $actor): PaymentTransaction {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($locked->status !== 'successful' || $locked->refund_status !== 'none') {
                throw ValidationException::withMessages(['refund' => 'Only successful transactions with no active or unresolved refund can be refunded.']);
            }
            $locked->update(['refund_status' => 'processing', 'refund_provider_status' => null, 'refund_requested_at' => now()]);
            AuditLog::record($actor, 'payments.refund_requested', $locked, ['gateway' => $locked->gateway, 'reference_hash' => hash('sha256', $locked->reference)]);
            return $locked->fresh();
        });

        try {
            $verifyRef = $claimed->gateway === 'flutterwave' ? $claimed->provider_reference : $claimed->reference;
            $verification = $provider->verify($claimed, $verifyRef);
            if (($verification['verified'] ?? false) !== true) {
                throw new PaymentGatewayException('The provider could not re-verify the original payment.');
            }
            $providerResult = $provider->refund($claimed);
            if (($providerResult['accepted'] ?? false) !== true) {
                throw new PaymentGatewayException('The provider did not accept the refund request.');
            }
            $providerStatus = strtolower(trim((string) ($providerResult['status'] ?? 'pending')));
            $workflowStatus = $this->workflowStatus($claimed->gateway, $providerStatus);

            DB::transaction(function () use ($claimed, $providerResult, $providerStatus, $workflowStatus, $actor): void {
                $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($claimed->id);
                if ($locked->refund_status !== 'processing') return;
                $locked->update([
                    'refund_status' => $workflowStatus,
                    'refund_provider_status' => substr($providerStatus, 0, 80),
                    'refund_reference' => isset($providerResult['reference']) && $providerResult['reference'] !== '' ? substr((string) $providerResult['reference'], 0, 190) : null,
                ]);
                if ($workflowStatus === 'completed') {
                    if (! $this->settlement->markRefunded($locked, $actor)) {
                        throw new PaymentGatewayException('The payment was not in a refundable successful state.');
                    }
                }
                AuditLog::record($actor, 'payments.refund_provider_response', $locked, ['gateway' => $locked->gateway, 'refund_status' => $workflowStatus, 'provider_status' => $providerStatus]);
            });
            return $claimed->fresh();
        } catch (Throwable $exception) {
            DB::transaction(function () use ($claimed, $actor): void {
                $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($claimed->id);
                if ($locked->refund_status === 'processing') {
                    $locked->update(['refund_status' => 'unknown', 'refund_provider_status' => 'unknown']);
                    AuditLog::record($actor, 'payments.refund_outcome_unknown', $locked, ['gateway' => $locked->gateway, 'refund_status' => 'unknown']);
                }
            });
            report($exception);
            throw ValidationException::withMessages(['refund' => 'The provider refund outcome could not be confirmed. Do not retry; check the provider dashboard and reconcile this transaction first.']);
        }
    }

    private function workflowStatus(string $gateway, string $providerStatus): string
    {
        if ($gateway === 'paystack') {
            return match ($providerStatus) {
                'processed' => 'completed',
                'failed' => 'failed',
                'needs-attention' => 'needs_attention',
                default => 'accepted',
            };
        }
        // Flutterwave documents `completed` as initiated and awaiting customer disbursement.
        return match ($providerStatus) {
            'completed-bank-transfer', 'completed-momo', 'completed-mpgs', 'completed-offline', 'completed-preauth' => 'completed',
            'failed' => 'failed',
            default => 'accepted',
        };
    }

    private function isConfigured(string $gateway): bool
    {
        return match ($gateway) {
            'paystack', 'flutterwave' => filled(config("services.{$gateway}.secret_key")),
            default => false,
        };
    }
}
