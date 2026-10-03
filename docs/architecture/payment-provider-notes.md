# Payment provider integration notes (verified sources)

These notes were checked against official provider documentation on 2026-09-30. They guide adapter boundaries only; they do not establish the school's merchant entitlements or credentials.

## Paystack

- Official webhook docs: https://paystack.com/docs/payments/webhooks/
- Official refunds guide: https://paystack.com/docs/payments/refunds/
- Paystack documents `x-paystack-signature` as an HMAC-SHA512 signature of the raw event payload using the merchant secret key. Validate before processing.
- A webhook endpoint should acknowledge accepted events with HTTP 200 promptly; Paystack documents repeated delivery when acknowledgement fails.
- A browser redirect/callback is not proof of payment. Use a server-side transaction verification call against the expected reference, amount and currency before marking the obligation successful.
- A refund request is `POST /refund` with the transaction ID or reference. Omitting `amount` requests a full refund. The API queues processing; acceptance is not completion. Documented states include `pending`, `processing`, `needs-attention`, `failed` and `processed`; the associated transaction is reversed only when the refund reaches `processed`. Paystack lists signed refund webhook events including `refund.pending`, `refund.processing`, `refund.needs-attention`, `refund.failed` and `refund.processed`.

## Flutterwave

- Official webhook docs: https://developer.flutterwave.com/docs/webhooks
- Official refunds guide: https://developer.flutterwave.com/docs/refunds
- The current webhook docs describe configuring a secret hash and checking the incoming `verif-hash` header against it. The docs caution against strict IP allow-listing because Flutterwave IP addresses may change.
- A webhook must return HTTP 200; retry behavior is configurable and the docs describe up to three retries at 30-minute intervals when enabled.
- Verify the transaction server-side and compare the provider's reference, amount and currency with the pending application record before changing state.
- A refund request is `POST /v3/transactions/{transaction_id}/refund`; it requires the provider transaction ID. The documented response status `completed` can mean refund initiated but still pending customer disbursement. The provider documents separate final statuses and advises fetching current refund status by refund ID or configuring a callback/webhook. Refund webhooks are not enabled by default and may require provider support to enable them. Do not mark the original transaction as reversed solely from an accepted initiation response.

## Moniepoint

- Official docs inspected: https://docs.pos.moniepoint.com/
- The examined API is explicitly the Moniepoint POS API: it documents pushing POS transactions through `POST /v1/transactions` and querying by unique merchant reference using `GET /v1/transactions/merchants/{merchantReference}`.
- The API also documents HTTPS webhook subscriptions and webhook subscription-event operations. Webhook subscription authentication options described in the docs include BASIC or NONE; the reviewed reference does not establish a generic browser-hosted online checkout flow or an HMAC-signature header.
- The application must not claim that this POS API provides the same online hosted-checkout flow as Paystack/Flutterwave. Keep Moniepoint configured as unavailable for online checkout unless the school confirms an appropriate product/contract and supplies authorized sandbox access. If using the documented POS path later, implement the provider's actual auth/delivery contract and record retrieval/verification; do not fabricate signature semantics.

## Shared application controls

Regardless of provider: generate unique server-side references; persist pending attempts; authenticate webhook headers/raw body according to the exact provider contract; use a unique provider event/transaction key for idempotency; verify transaction status, amount, currency and reference server-side; never trust a client return URL; never store PAN/CVV; and never log secrets or raw sensitive payment data. A provider-accepted refund request remains distinct from a confirmed/processed refund; ambiguous outcomes require reconciliation rather than automatic retry.
