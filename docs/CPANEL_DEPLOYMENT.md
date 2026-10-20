# Deploying Emmaculate Academy on cPanel hosting

Render is only a test site. This is the production route.

## 0. What the hosting must offer (ask the host before you pay)
- PHP **8.3** with these extensions: pdo_mysql, mbstring, intl, gd, zip, bcmath, fileinfo, xml, curl, openssl, opcache.
- MySQL 5.7+ or MariaDB 10.3+ (utf8mb4). PostgreSQL also works if your plan has it.
- Terminal or SSH, cron jobs, free AutoSSL certificate, and the ability to change the domain's document root.
- LiteSpeed web server is best. At least 1-2 GB RAM and a generous "entry processes" limit (50+). See docs/TRAFFIC_PLAN_CPANEL.md.

## 1. Get the release ZIP (no computer tools needed)
Easiest: on GitHub open the repository > **Actions** > **Build cPanel release** > **Run workflow**. When it finishes (about 5 minutes), open the run and download the `cpanel-release` file (a ZIP). This ZIP already contains the PHP packages and the built CSS/JS.

Alternative on a computer with PHP 8.3, Composer and Node: `bash deploy/build-cpanel-release.sh`.

## 2. Create the database
cPanel > MySQL Databases: create a database and a user, add the user to the database with ALL PRIVILEGES. Note the full names (they start with your cPanel username).

## 3. Upload
File Manager > upload the zip to `/home/USERNAME/` and extract it into a folder named `emmaculate` (NOT inside public_html).

## 4. Point the domain at the `public` folder
cPanel > Domains > Manage > Document Root = `/home/USERNAME/emmaculate/public`.
(If your host does not allow this: copy the contents of `public/` into `public_html/` and change the two paths in `public_html/index.php` to `/home/USERNAME/emmaculate/vendor/autoload.php` and `/home/USERNAME/emmaculate/bootstrap/app.php`.)

## 5. Settings file
Copy `.env.cpanel.example` to `.env` in `/home/USERNAME/emmaculate/`, fill in the database, `APP_URL`, mail and the three `SCHOOL_ADMIN_*` lines.

## 6. First-time commands (cPanel > Terminal)
```
cd ~/emmaculate
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force
php artisan migrate --force
php artisan school:init
php artisan storage:link
php artisan optimize
```
Then delete the `SCHOOL_ADMIN_*` lines from `.env`, run `php artisan config:clear && php artisan optimize`, and sign in at `/admin`.
If `storage:link` is blocked, make the link `public/storage -> ../storage/app/public` in File Manager (or copy the folder).

### No Terminal on your plan?
Some cheap cPanel plans have no Terminal. Use **cPanel > Cron Jobs** instead: add a job set to run once (pick a time two minutes ahead) with this command, then delete the job afterwards:
```
/usr/local/bin/php /home/USERNAME/emmaculate/artisan migrate --force && /usr/local/bin/php /home/USERNAME/emmaculate/artisan school:init && /usr/local/bin/php /home/USERNAME/emmaculate/artisan storage:link && /usr/local/bin/php /home/USERNAME/emmaculate/artisan optimize
```
Put your own `APP_KEY` in `.env` first (generate one at https://generate-random.org/laravel-key-generator, or on any computer with `php artisan key:generate --show`).

## 7. Cron (cPanel > Cron Jobs, every minute)
```
/usr/local/bin/php /home/USERNAME/emmaculate/artisan schedule:run >> /dev/null 2>&1
```

## 8. Updating later
Upload the new release over the old one (keep `.env` and `storage/app`), then:
```
php artisan migrate --force && php artisan school:init && php artisan optimize:clear && php artisan optimize
```

## 9. Before going live
- Delete the demo accounts (`@demo.emmaculate.test`) and demo class/students.
- Set the real phone, email, social links in Admin > School settings.
- Set up email (docs/EMAIL_SETUP.md), put Cloudflare in front (docs/TRAFFIC_PLAN_CPANEL.md) and switch on the sign-in check (docs/SECURITY.md).
- In School settings check the **Student ID prefix**, then use Students > *Assign Student IDs to older records* and People > PIN slips.
- Back up: cPanel > Backup (database + files) on a schedule, and download a copy.
