# Handling a lot of traffic on cPanel hosting

## Already built into the code
- Settings, menus, footer, home page and gallery data are cached for a few minutes; saving anything in the admin refreshes the cache instantly. A normal public page needs almost no database queries.
- Static files (CSS, JS, images) are compressed and cached by browsers for long periods (public/.htaccess).
- Indexes on the busy tables; rate limits per visitor; student sign-in and Check Result lock repeated wrong guesses.
- Sessions, cache and queue use files/sync (no Redis needed on shared hosting).

## What decides the real ceiling: the hosting plan
Shared cPanel plans cap how many PHP requests run at once (often 20-50) and how much CPU you may use. With the caching above, a good shared/LiteSpeed plan can serve hundreds of people browsing at once. "Thousands at the same moment" on results day needs more headroom:
1. Prefer LiteSpeed + PHP 8.3 with OPcache on (cPanel > MultiPHP INI Editor: opcache.enable=1, memory_limit=256M).
2. Put **Cloudflare (free)** in front: it serves images/CSS/JS from its own servers (most of the bytes), blocks attacks and bots, and adds the "are you a robot" check (Turnstile).
   - Cache rule: cache `/build/*`, `/storage/*`, and image files.
   - Bypass cache: `/admin*`, `/portal*`, `/login`, `/student-login`, `/check-result*`, `/contact`.
3. If load tests (below) show the plan struggling, move to a cPanel **VPS** (4 vCPU / 8 GB RAM) or managed cloud. The same release zip runs there.

## Results-day habits that matter more than any setting
- Enter and check results days ahead; publish just before the announcement, and announce in waves (by class) so everyone does not arrive in the same minute.
- Encourage viewing results on screen; PDF creation is the most expensive action (it is rate limited).
- Warm the cache: open the home page and Check Result page yourself after publishing.

## Prove it before results day
Run `deploy/loadtest.js` with k6 against the live site at a quiet time. Watch cPanel > Resource Usage for "entry processes" and CPU hitting the limit. If it does, upgrade the plan or ask the host to raise limits.

## Later improvements (optional)
Queue PDF generation, store uploads on Cloudflare R2 with a CDN, add Redis on a VPS, edge-cache anonymous HTML pages.
