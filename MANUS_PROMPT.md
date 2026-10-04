Task: apply the "Public website redesign (update 1)" to the existing Laravel repo addigitaltech/emmaculate_full_website and deploy it. Do NOT rebuild or restructure anything.

1. Copy every file from the attached folder `emmaculate-ui-update` into the repo root, keeping the same relative paths (overwrite existing files). Read CHANGES.md first.

2. Before committing, run these checks and fix any error you find (these files were written without a PHP runtime, so they have not been executed yet):
   - `composer install`
   - `php -l` on every new or changed .php file listed in CHANGES.md
   - `php artisan migrate --force` against a scratch database, then `php artisan db:seed --force` (the new DesignRefreshSeeder must run twice without errors or duplicate rows)
   - `php artisan view:cache` and `php artisan route:list` (they must complete with no Blade or route errors)
   - `php artisan test`
   - `npm ci && npm run build`
   - Open /, /about, /history, /mission-vision, /academics, /admissions, /news, /gallery, /contact, /faq in a browser and confirm there are no errors and no horizontal scrolling at 390px wide.
   If you must change a file to fix an error, keep the visual design and the editable-in-admin behaviour unchanged, and tell me exactly what you changed.

3. Do not change the Results Portal, teacher/student/parent portals, payments, roles or permissions.

4. Commit to main with the message "Public website redesign (update 1)" and push. Render redeploys automatically.

5. Report back: files changed beyond the package, every error you fixed, and the result of each check above.
