# Deploying Emmaculate Academy on cPanel hosting

Render is only a test site. This is the production route.

## 0. What the hosting must offer (ask the host before you pay)
- PHP **8.3** with these extensions: pdo_mysql, mbstring, intl, gd, zip, bcmath, fileinfo, xml, curl, openssl, opcache.
- MySQL 5.7+ or MariaDB 10.3+ (utf8mb4). PostgreSQL also works if your plan has it.
- Terminal or SSH, cron jobs, free AutoSSL certificate, and the ability to change the domain's document root.
- LiteSpeed web server is best. At least 1-2 GB RAM and a generous "entry processes" limit (50+). See docs/TRAFFIC_PLAN_CPANEL.md.

## 1. Build the release (on a computer or Termux, not on the server)
```
bash deploy/build-cpanel-release.sh
```
This installs production packages, builds the CSS/JS and creates `release-YYYYMMDD-HHMM.zip`.

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
- Set up email (docs/EMAIL_SETUP.md) and put Cloudflare in front (docs/TRAFFIC_PLAN_CPANEL.md).
- Back up: cPanel > Backup (database + files) on a schedule, and download a copy.
