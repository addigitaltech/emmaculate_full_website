# Update 10 — payments upgrade, security hardening, pictures that always show

## Payments
1. **Debts visible**: student and parent portals show total owing, each fee (charged, paid, balance, due date, part paid / overdue) and the payment history with printable receipts.
2. **Assign fees to classes and arms**: Fees & payments > *Assign fees to classes* (skips students who already have the fee).
3. **Full payment or installments** (Settings > Portal & website switches): installments with a minimum instalment, or full payment where all of a student's unpaid fees are added together.
4. **Input is never trusted**: amounts, idempotency keys, names and report filters are validated and cleaned on the server before anything goes to a payment provider; balances come from the database; one payment at a time per student; spreadsheet downloads cannot carry formulas.
5. **Notifications**: on every verified payment the school gets an email alert (and an SMS when Termii is set up), the payer gets an email with the **receipt attached**, and the receipt can be printed from the portal (HTML or PDF). Sent once only; failures never undo a payment.
6. **Payment history and debts** (Fees & payments > *Payment history & debts*): dates or a whole session, class/arm/search; **print, PDF or spreadsheet**, with who still owes.

## Security
- **Bot and probe blocking** before the application runs (WordPress/phpMyAdmin/.env/.git/script requests, directory tricks, oversized addresses), a **per-visitor rate limit** on every page, and the Cloudflare **"I am not a robot" check on the contact and admission forms** as well as every sign-in (the newsletter box keeps its hidden-field trap and rate limit).
- **Server files protected**: `.htaccess` rules block hidden files, secrets, logs and backups; uploaded files can never run as scripts; a root `.htaccess` keeps project folders private on XAMPP-style setups.
- **Login cookies are secure by default in production.**
- **Settings > Security check**: a plain-language check of the settings that matter (debug off, https, secure cookies, Turnstile, mail, payment keys, leftover demo logins, number of Super Admins, PHP version).
- **Weekly automatic security audit** of PHP and JavaScript packages (GitHub Actions) and Dependabot update requests.

## Pictures
- **Pictures show on XAMPP and any host**: addresses of uploaded pictures now work with or without the `public/storage` link and whatever address the site is opened on (a safe `/media/...` route is used when the link is missing). This fixes student photos not showing on results and in the admin.
- **Settings > Pictures check & backup**: lists any picture the database points to that is missing, downloads **all pictures as one ZIP** (with a list of where each is used) and loads such a ZIP into another site (never overwrites; refuses scripts and fake pictures). To keep them in the project files, unzip into `storage/app/public/`.
- **Finding about your live site**: the 8 original school pictures are in the project files, but two pictures uploaded through the admin (a media-library copy and a student passport) live only on Render's temporary disk and are lost whenever Render redeploys. Use *Download all pictures* now, and for lasting storage use cPanel hosting, a paid Render disk, or cloud storage (docs/PERFORMANCE.md).

## Database
Additive migration `2026_10_29_000001_payments_installments_and_notifications`: `amount_paid` on fees, `payment_allocations`, `notified_at` on payments, and payment settings. Existing paid fees are marked fully paid. Nothing is dropped.

## After deploying
1. Portal switches: choose installments or full payment, the smallest installment, the bursar's email(s) and phone(s).
2. Add SMS keys if you want texts (docs/PAYMENTS.md).
3. Settings > Security check: fix anything marked with a warning.
4. Settings > Pictures check & backup: *Check pictures*, then *Download all pictures* and keep the ZIP.
5. Fees & payments > *Assign fees to classes* when a new term's fees are ready.

## Not yet verified
Written without being able to run PHP. Please try a test payment in the provider's test mode (installment and full), check the emails and receipt, and try the new pages.
