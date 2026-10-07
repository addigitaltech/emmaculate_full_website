# Making "Forgot password" send real emails

The code already supports password reset by email (/forgot-password -> email link -> /reset-password). It only needs an email service. Until one is configured, no email is delivered (the site writes it to its log instead).

## 1. Pick an email service (all have a free tier)
- Brevo (300 emails/day free, SMTP), Resend (100/day free), Mailgun, or the school's own email host's SMTP.
- Verify the sender address/domain with the provider (add the SPF and DKIM records they give you) or messages will land in spam.

## 2. Set these environment variables on Render (Environment tab), then redeploy
```
MAIL_MAILER=smtp
MAIL_HOST=<provider SMTP host>
MAIL_PORT=587
MAIL_USERNAME=<provider SMTP login>
MAIL_PASSWORD=<provider SMTP key>
MAIL_SCHEME=null
MAIL_FROM_ADDRESS=noreply@<your verified domain>
MAIL_FROM_NAME="Emmaculate Academy"
APP_URL=https://<your real site address>
```
(`APP_URL` must be the real https address, otherwise the reset link in the email points to the wrong place.)

## 3. Who can reset what
- Parents and staff with an email: use "Forgot your password?" on the sign-in page.
- Students without an email: nothing to reset. They sign in with admission number + surname.
- Anyone whose email is wrong or missing: an admin opens Admin > Users, edits the user and types a new password.
