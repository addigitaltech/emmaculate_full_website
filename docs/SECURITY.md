# Keeping the website secure

## Sign-in protection with Cloudflare Turnstile (free)
Turnstile is a friendly "I am not a robot" check. When switched on it appears on the **admin sign-in, staff, parent and student sign-in, Forgot password and the Check Result page**.

1. Make a free Cloudflare account, open **Turnstile** and add your website's domain. Choose *Managed*.
2. Copy the **Site key** and the **Secret key**.
3. Put them in the `.env` file (cPanel File Manager, or the Render dashboard > Environment):
   ```
   TURNSTILE_SITE_KEY=0x4AAAA...
   TURNSTILE_SECRET_KEY=0x4AAAA...
   ```
4. Clear the cache (cPanel Terminal: `php artisan config:clear`; on Render, redeploy).

Leave both empty to switch it off (for example on XAMPP on your own computer). If Cloudflare cannot be reached, people cannot sign in until it is back; to switch the check off in an emergency, empty the two lines.

## Put Cloudflare in front of the whole site (recommended)
Point your domain's nameservers to Cloudflare (free plan). It hides the server address, serves pictures/CSS/JS from its own network, blocks attacks and bots, and keeps the site up through traffic spikes. See `docs/TRAFFIC_PLAN_CPANEL.md` for the cache rules.

## What the code already does
- Student sign-in and the Check Result page lock repeated wrong guesses **per student**, and PINs lock 15 minutes, then 30, 60 ... after every 5 wrong tries.
- Passwords need 12+ characters (upper and lower case, a number and a symbol). Reset links are sent by email.
- Every page sends strict security headers (content security policy, no framing, HTTPS only).
- Payment confirmations are checked with the payment company before any fee is marked paid.
- Staff pages check permissions on the server, not only in the menu. The activity log records who published, unpublished or changed results, PINs and payments.

## Your habits matter most
- Give each person their own login; never share the Super Admin login. Remove accounts of people who leave.
- Keep the PIN slips private; reissue a PIN if one is lost.
- Back up the database and files regularly and keep a copy off the server.
