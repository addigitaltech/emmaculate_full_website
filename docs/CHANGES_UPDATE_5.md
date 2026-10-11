# Update 5 — simpler admin, picture picker, emails, settings pages
Apply AFTER Update 4. This package is cumulative for the files it contains (it also supersedes "Admin fix 2.1": the two bugs fixed there are fixed here too). Merge changes; keep earlier fixes; do not duplicate.

## Fixes for the 500 errors reported
- Announcements, news, events, gallery, programmes, leaders: picture fields were upload boxes wired to numeric ID columns (the database error was `invalid input syntax for type bigint: "01M4…png"`). They now use a **picture picker**: choose from the Media library or press "+" to upload from phone/computer (saved into the Media library, validated as a real image, max 5 MB).
- List pages used `->numeric()` on image columns; URL-rule closures were passed to Filament the wrong way; the programme and news forms put text boxes on list-type fields. All replaced with proper fields.
- New regression test opens every admin list/create page as Super Admin.

## Easier to manage
- Admin menu regrouped: Website, School profile, Homepage & menus, Admissions & messages, People, Academics setup, Results, Fees & payments, System. Technical screens (grade bands, assessment setup, remarks, ratings, payment events, receipts, portal links, old settings) are hidden from the menu.
- New pages: **School profile** (details, logo, contact, GPS map, social, colours, mission/vision/pledge/anthem, homepage numbers), **Assessment & grading** (marks split, pass mark and grades once for all classes; replaces the per-class setup), **Portal & website switches** (Super Admin: student portal, parent portal, Check Result, hold results for fees, show/hide footer "Admin login"), **Send an email**.
- News/events/announcements: plain labels, Published/Unpublished choice with a one-click toggle, no web-address or publish-date typing (auto-made). Existing mission/vision/pledge/anthem text is moved into School profile once.
- Gallery albums edit their photos inside the album.
- docs/ADMIN_GUIDE.md: "where do I change X?" table for the person managing the site.

## Other requests
- Contact page shows a map (OpenStreetMap) with "Get directions"/"Open in Google Maps" when a GPS location is saved in School profile. Content-Security-Policy updated (frame-src) and the admin console now gets the script permissions Filament needs.
- Footer credit: "Designed by Addigitaltech for Education" links to /designed-by with WhatsApp and website buttons (number/URL not printed). Admin login in footer can be hidden (System > Portal & website switches). Admin login page has "Back to the school website".
- Emails (queued, sent a few per minute by the cron job; set QUEUE_CONNECTION=database and run `php artisan schedule:run` every minute): application received (to the guardian and the school), automatic email once for each newly published news item/event (can be switched off per item, existing posts are never emailed), manual "Send an email" to groups, unsubscribe link for newsletter subscribers. Placeholder/demo/student-without-email addresses are skipped.
- Assessment maxima now come only from the single school-wide setting.

## Migration
2026_10_09_000001: school_settings (gps_location, show_admin_link, mission, vision, pledge, anthem, identity_closing); news_posts and school_events (send_email, notified_at; existing rows marked as already notified).
