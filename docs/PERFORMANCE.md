# Handling heavy traffic (results day)

This page lists what the code and the Docker image do, and what to set on the hosting. See also `docs/TRAFFIC_PLAN_CPANEL.md`.

## Built in
- **Caching:** school settings, menus, footer, home page and gallery data are cached for minutes and refresh instantly when an admin saves.
- **Database indexes** on the busiest tables (results by term and status, fees, payments, activity log). PostgreSQL does not index foreign keys by itself, so Update 9 adds them.
- **Result checking** needs one student lookup and one results query. The Session/Term drop-downs are a single light query.
- **Compression and long browser caching** for CSS, JS, fonts and images (`public/.htaccess`).
- **Rate limits** on sign-in, Check Result, payments and PDF downloads.
- **Docker image (Render and similar):** tuned PHP OPcache, and Apache limited to 20 workers so a small server is never driven out of memory; a health check at `/up`.

## Set these on the host
| Where | Setting | Why |
|---|---|---|
| Any host | Put **Cloudflare** in front (free) and cache `/build/*`, `/storage/*`, images | Most traffic is pictures and files |
| One server | `SESSION_DRIVER=file`, `CACHE_STORE=file` | Fewer database calls per page |
| Several servers | `SESSION_DRIVER=redis`, `CACHE_STORE=redis` | Shared by all servers |
| Render | Use a paid instance (not the free one) and a paid database; set the health check path to `/up` | The free plan sleeps and is small |
| cPanel | PHP 8.3 with OPcache on, `memory_limit=256M`; a plan with 50+ entry processes | Controls how many people are served at once |

## Before results day
1. Enter and check results days ahead; publish just before the announcement, class by class.
2. Open the home page and Check Result page yourself after publishing (warms the cache).
3. Run a load test with `deploy/loadtest.js` (k6) at a quiet time and watch CPU and "entry processes".
4. Keep one full backup the evening before.
