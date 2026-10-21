# Keeping the website secure

## Sign-in protection with Cloudflare Turnstile (free)
Turnstile is a friendly "I am not a robot" check. When switched on it appears on the **admin sign-in, staff, parent and student sign-in, Forgot password and the Check Result page**. Each form names itself to Cloudflare (`admin_login`, `portal_login`, `student_login`, `password_reset`, `check_result`), and the server accepts a token only if Cloudflare says it was earned on *that* form and on *this* website, so a token cannot be reused elsewhere.

**You need both keys.** The *site key* is public and goes in the page. The *secret key* is private: it lets the server ask Cloudflare whether a token is genuine. Never paste the secret into chat, email, the code or GitHub.

1. In Cloudflare open **Turnstile**, choose your widget and check its **Domains** list contains every address the site is served on (for example your `onrender.com` address, your real domain with and without `www`, and `localhost` only for testing on your own computer).
2. Copy the **Site key** and the **Secret key** (Settings of the widget).
3. Put them in the host's private settings, not in the code:
   - **Render:** service > **Environment** > add `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY`. Render redeploys by itself.
   - **cPanel / XAMPP:** the `.env` file (never in a public folder). Then `php artisan config:clear`.
   ```
   TURNSTILE_SITE_KEY=0x4AAAA...
   TURNSTILE_SECRET_KEY=0x4AAAA...
   ```
4. Optional: `TURNSTILE_HOSTNAMES=www.yourschool.org,yourschool.org` to name the allowed addresses exactly. Left empty, the site accepts the address the visitor used, which is safe because Cloudflare only issues tokens for the domains registered on the widget.
5. Try it: each form should show the widget, a normal sign-in should work, and a second use of the same token is refused by Cloudflare automatically (a token works once).

Leave both keys empty to switch the check off (for example on XAMPP). If Cloudflare cannot be reached, people cannot sign in until it is back; to switch the check off in an emergency, empty the two settings.

**Trying Cloudflare's dummy test keys?** They report a fake hostname and action, so also set `TURNSTILE_STRICT_CONTEXT=false` while testing, and remove that line for the real keys.

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
