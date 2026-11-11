# School fees and payments

## How fees work
1. **Fee types** (Fees & payments > Fee types): Tuition, Uniform, Bus ...
2. **Assign fees to classes** (Fees & payments > Assign fees to classes): pick the fee type, the amount for each student, the session (and term), then the classes and, if you like, one arm. Every active student of those classes is charged. Students who already have that fee for that session and term are skipped, so pressing the button twice never charges anyone twice. Single students can still be charged one by one under *Fees owed*.
3. **Parents and students see the debt** in their portal: total still owing, each fee with what was charged, what is paid and the balance, and the payment history with printable receipts.

## Full payment or installments (Settings > Portal & website switches)
- **Installments allowed:** a parent can pay a fee in one go or in parts. Every part must be at least the *smallest installment* you set (or the balance, if that is less) and never more than the balance. A fee shows as *Part paid* until it is cleared.
- **Full payment only:** all of a student's unpaid fees are added together and paid in one payment.

## What happens when someone pays
1. The parent chooses Paystack or Flutterwave and pays on the provider's secure page.
2. The website never trusts the page the parent returns to: the server asks the provider whether the payment really happened and checks the amount, currency and reference. Only then is the fee marked paid.
3. A receipt is created. The parent (or the student's parents, when a student paid) gets an **email with the receipt attached**; the school gets an **email alert**; and, when SMS is set up, the school gets a **text message** (and parents too if you switch that on).
4. Each payment is announced once only, even if the provider calls twice. If an email or text fails, the payment is still recorded.
5. Receipts can be printed again at any time from the portal.

## Payment history and debts (Fees & payments > Payment history & debts)
Pick **dates** or a **whole session** (and optionally a class or arm, or search a student). The page shows totals, every payment and everyone who still owes. **Print**, **Download PDF** or **Download spreadsheet**.

## Setting up notifications
- **Email:** make sure email works (docs/EMAIL_SETUP.md). In Portal switches, list the bursar's email(s); Finance Admin users are always included.
- **SMS (optional):** create an account at termii.com, then add to the `.env` file (or Render > Environment):
  ```
  SMS_DRIVER=termii
  TERMII_API_KEY=...
  TERMII_SENDER_ID=YourSchool
  ```
  Then list the phone numbers to text in Portal switches (Nigerian numbers such as 08031234567). Never put the key in chat, code or GitHub.

## Payment gateway webhook configuration

Both gateways use public HTTPS POST endpoints. Replace `YOUR-DOMAIN` with the final school domain and register the exact URL in the provider's merchant dashboard:

| Gateway | Webhook endpoint | Required authentication setting |
|---|---|---|
| Paystack | `https://YOUR-DOMAIN/api/webhooks/payments/paystack` | Paystack sends `x-paystack-signature`; Laravel verifies an HMAC-SHA512 signature using `PAYSTACK_SECRET_KEY`. |
| Flutterwave | `https://YOUR-DOMAIN/api/webhooks/payments/flutterwave` | Flutterwave sends `verif-hash`; it must equal `FLUTTERWAVE_SECRET_HASH`. |

The matching server configuration is:

```dotenv
PAYSTACK_SECRET_KEY=your-private-secret
PAYSTACK_BASE_URL=https://api.paystack.co

FLUTTERWAVE_SECRET_KEY=your-secret-key
FLUTTERWAVE_SECRET_HASH=your-webhook-secret-hash
FLUTTERWAVE_PUBLIC_KEY=your-public-key
FLUTTERWAVE_BASE_URL=https://api.flutterwave.com/v3
```

`PAYSTACK_SECRET_KEY`, `FLUTTERWAVE_SECRET_KEY` and `FLUTTERWAVE_SECRET_HASH` must be set before the corresponding gateway can verify payments. The public key is retained for frontend integrations, while the current checkout flow uses the server-side secret key and provider redirect.

The webhook endpoint does not use a logged-in session or CSRF token. It must remain reachable with a direct POST request and must not be protected by Basic Auth, a login redirect, a cache rule or a maintenance page. It is rate-limited to 120 requests per minute per source IP.

Paystack `charge.success` and Flutterwave successful `charge.completed` events are verified again through the provider API before fees are marked paid. Invalid signatures are rejected with HTTP 401. Temporary processing failures return HTTP 500 so the provider can retry. Repeated delivery of an already processed event is acknowledged without creating another receipt or settling the fee twice. The event status is stored in `payment_events` for audit and troubleshooting.

After editing the production `.env`, refresh Laravel's cached configuration:

```bash
php artisan optimize:clear
php artisan config:cache
```

### Safe webhook test

Use provider test mode before live mode. Create one disposable fee assignment, complete one test checkout, then confirm all of the following:

1. The provider delivery log shows the exact endpoint and a successful HTTP response.
2. The local transaction is `successful` only after provider verification.
3. The fee balance and receipt are correct.
4. The matching `payment_events` row is `processed`.
5. Replaying the same event does not create another receipt or change the fee a second time.

The repository regression suite for this behavior is `tests/Feature/PaymentWebhookTest.php`. Never expose provider secrets in chat, screenshots, source control or URLs.

## Safety built in
Only a plain naira amount is accepted, the fee and balance come from the database (never from the browser), one payment at a time per student, amounts are capped, names sent to the provider are cleaned, provider addresses are checked, webhooks are signature-checked, duplicate events are idempotent, and every change is in the activity log.
