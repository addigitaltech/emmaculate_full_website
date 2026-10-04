# Emmaculate Academy — Public website redesign (update 1)

Copy every file in this folder into the project root, keeping the same paths, then commit and push to `main`.
Nothing here deletes a file. Results Portal, teacher/student/parent portals and payments are untouched.

## What changed
- **Design system** (`resources/css/app.css`): navy / maroon / gold palette, serif headings, buttons, cards, bands, forms, tables, footer — taken from the approved mockups.
- **Layout, header, footer**: top contact bar, sticky header, About/Academics/News dropdowns, "Portals" menu, mobile menu, footer with Quick Links / Portals / Contact / Newsletter, WhatsApp button, back-to-top.
- **Pages restyled to the mockups**: Home, About, Mission/Vision/Pledge/Anthem (`/mission-vision`), History (`/history`), Academics, Admissions, News & Events, Gallery (filters + lightbox). Also restyled in the same system: Contact, FAQ, Leadership, Programme, Events, Announcements, Portals, generic pages.
- **Everything stays editable in the admin** — nothing is hard-coded except fallback wording taken from the mockups:
  - Logo, name, motto, phone, email, address, social links, WhatsApp, opening hours: *School settings*.
  - Colours: *School settings → theme tokens*, keys `primary`, `secondary`, `accent` (hex like `#041433`).
  - Page banner photos: *School settings → global settings*, key `hero_about`, `hero_history`, `hero_mission`, `hero_academics`, `hero_admissions`, `hero_news`, `hero_gallery`, `hero_contact`, `hero_leadership`, `hero_faq` (value = media-library ID).
  - NEW **Website → Page sections**: core values, "why choose us", academics blocks, admission steps, academic levels, banner texts.
  - NEW **Website → Newsletter subscribers** (read-only list). The footer / news-page newsletter forms now save emails.
  - History page: in the page editor use the new block type **"Section title (starts a new card)"**; a Heading followed by a List becomes a name-list box; a Quote becomes the prayer note.
- **Fixes found on the way**
  - The old header used Alpine inline scripts, which the site's Content-Security-Policy blocks, so the mobile menu could not work. Replaced with plain JavaScript (`resources/js/site.js`).
  - Page titles are no longer double-escaped (e.g. "News &amp;amp; Events").
- **Deployment helpers**
  - `Dockerfile` now runs `php artisan school:init` on every start: seeds roles/content on a brand-new database, applies the design content once, and can create the first Super Admin from environment variables (below).
  - `.dockerignore` added.

## First admin account on Render (free plan has no shell)
Set these in Render → Environment, deploy once, then DELETE all three:
`SCHOOL_ADMIN_NAME`, `SCHOOL_ADMIN_EMAIL`, `SCHOOL_ADMIN_PASSWORD` (12+ characters with upper/lower case, a number and a symbol).
It only creates an account if no Super Admin exists.

## Not done yet / needs your decision
- Site search icon from the mockups (no search feature exists yet).
- Real phone numbers, email, social links and school statistics (the mockup numbers are placeholders; the site shows only what you enter).
- FAQ answers (only the questions were in the mockup).
- Public "upload your photos" on the gallery was replaced by a "Send us your photos" link to the contact form, so nothing public goes live unchecked.
- The logo file reads "EMMACULATE - COLLEGE" while the mockups say "Emmaculate Academy". Please confirm which is correct.

## Verified / not verified
Verified: every page renders in an offline preview at desktop and phone width with no horizontal overflow; menu, hero slider, gallery filter and lightbox behave.
NOT verified: this was prepared without a PHP runtime, so the Laravel code has not been executed. See `MANUS_PROMPT.md` for the checks to run before pushing.
