# Update 9C — Turnstile finished the way Cloudflare's guide requires

Update 9 added Cloudflare Turnstile to every sign-in. This update tightens it to match Cloudflare's own integration guide for an existing widget:

- **Each form names itself** to Cloudflare (`data-action`): `admin_login`, `portal_login` (staff and parent), `student_login`, `password_reset`, `check_result`.
- **The server checks the answer, not just "success".** A token is accepted only if Cloudflare reports the same action for that form and the hostname of this website, so a token from another form or another site is refused. Tokens are used once; a failed attempt shows a fresh check.
- New optional settings: `TURNSTILE_HOSTNAMES` (exact allowed addresses; empty means the address the visitor used) and `TURNSTILE_STRICT_CONTEXT` (keep `true`; `false` only for Cloudflare's dummy test keys).
- The site key is public and is set per deployment with the secret key in the host's private settings (Render > Environment, or `.env` on cPanel/XAMPP). **The secret key is never pasted into chat, code or GitHub.** Steps: `docs/SECURITY.md`.

Files: `app/Support/Turnstile.php`, `app/Rules/TurnstileCaptcha.php`, `config/services.php`, `app/Http/Controllers/AuthController.php`, `StudentAccessController.php`, `ResultCheckController.php`, `app/Filament/Pages/Auth/Login.php`, `resources/views/components/turnstile.blade.php`, `resources/views/filament/forms/turnstile.blade.php`, `resources/views/auth/parent-login.blade.php`, `staff-login.blade.php`, `student-login.blade.php`, `forgot-password.blade.php`, `resources/views/site/check-result.blade.php`, `tests/Feature/TurnstileTest.php`, `docs/SECURITY.md`, `.env.example`, `.env.cpanel.example`. No migrations.
