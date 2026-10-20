# Running the website on your own computer (XAMPP, Laragon, Windows, Mac or Linux)

Useful for trying things out or for a school that runs it on a local server. For a public website use cPanel (`docs/CPANEL_DEPLOYMENT.md`) or any host that runs PHP 8.3.

## What you need
- **PHP 8.3 or newer.** Older XAMPP downloads ship PHP 8.2, which will NOT work; download an XAMPP build with PHP 8.3+ (or use Laragon, which lets you pick PHP 8.3).
- **MySQL or MariaDB** (included in XAMPP) or PostgreSQL.
- **Composer 2** (getcomposer.org) and **Node.js 20+** (nodejs.org).
- In `php.ini` (XAMPP Control Panel > Apache > Config) make sure these lines are NOT commented out (no `;` in front): `extension=curl`, `extension=fileinfo`, `extension=gd`, `extension=intl`, `extension=mbstring`, `extension=openssl`, `extension=pdo_mysql`, `extension=zip`, `extension=bcmath`, `extension=exif`. Restart Apache afterwards.

## Steps
1. Copy the project folder to `C:\xampp\htdocs\school` (or anywhere).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel. Open `http://localhost/phpmyadmin`, create a database named `school` with collation `utf8mb4_unicode_ci`.
3. In the project folder: copy `.env.example` to `.env` and set:
   ```
   APP_URL=http://localhost:8000
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=school
   DB_USERNAME=root
   DB_PASSWORD=
   SESSION_DRIVER=file
   CACHE_STORE=file
   ```
   (Leave `TURNSTILE_*` empty on your own computer.)
4. Open a terminal (Command Prompt or PowerShell) in the project folder and run:
   ```
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm ci
   npm run build
   php artisan school:create-super-admin
   php artisan serve
   ```
   On Windows, `storage:link` may need the terminal opened as Administrator.
5. Open `http://localhost:8000` for the website and `http://localhost:8000/admin` for the admin area.

## Using Apache instead of `php artisan serve`
Point a virtual host's DocumentRoot at the project's `public` folder (not the project folder itself) and enable `mod_rewrite`. The `.htaccess` in `public` does the rest.

## Common problems
- *"Your PHP version does not satisfy the requirement"*: you are on PHP 8.2 or older. Install PHP 8.3+.
- *Blank page or 500 error*: run `php artisan config:clear`, check `storage/logs/laravel.log`, and make sure `storage` and `bootstrap/cache` are writable.
- *Pictures do not show*: run `php artisan storage:link` again.
- *Styles look broken*: run `npm run build`.
- *Emails*: set the `MAIL_*` lines (see `docs/EMAIL_SETUP.md`) or use `MAIL_MAILER=log` to write emails to the log while testing.
