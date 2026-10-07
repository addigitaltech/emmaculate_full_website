# Handling thousands of visitors, and bot protection (to do after development)

Results day means many people arriving at once. The software can be tuned for it, but the hosting plan decides the ceiling. A free Render instance sleeps when idle and has a fraction of a CPU, so it will struggle with hundreds of simultaneous users, never mind thousands.

## Hosting (biggest effect)
1. Move the web service to a paid Render instance (Standard or higher) and turn on autoscaling (2+ instances).
2. Put Cloudflare (free) in front of the site: it caches the public pages and images, absorbs attacks, and gives free bot checks.
3. Use Neon's pooled connection string (the one with `-pooler` in the host) for the running app, and keep the direct one only for migrations. Raise the Neon compute size for results week.

## Application changes (I can build these)
1. Switch cache, sessions and queues from the database to Redis (Render Key Value). Today every page view touches the database for sessions and cache.
2. Cache the public pages and the school settings/menu for a few minutes; cache each published report for its owner.
3. Generate PDF reports in a queue instead of during the request, and limit how often one person can download.
4. Serve uploaded photos from object storage (Cloudflare R2 or S3) with a CDN. This is also needed so uploads survive redeploys.
5. Load-test with k6 before results day (for example 2,000 users opening the Check Result page and a report).

## "Are you a robot?" (CAPTCHA)
Use Cloudflare Turnstile (free, usually invisible). Add it to: sign-in, student sign-in, Check Result, contact form, newsletter and apply-online forms. The server verifies the token before processing. Already in place: per-IP and per-account rate limits, and a 15-minute lock after 5 wrong attempts on student sign-in and Check Result.

## Order I suggest
Finish development -> Redis + caching + queued PDFs -> R2 storage -> Cloudflare + Turnstile -> paid autoscaling hosting -> load test.
