<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Support\PortalAccess;
use App\Models\AuditLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /** The sign-in page for one portal (student, parent or staff). A switched-off portal shows a notice instead of a form. */
    public function showPortal(Request $request): View
    {
        $portal = (string) $request->route('portal');
        abort_unless(in_array($portal, ['student', 'parent', 'staff'], true), 404);

        return view(PortalAccess::enabled($portal) ? 'auth.'.$portal.'-login' : 'auth.portal-off', ['portal' => $portal]);
    }

    public function login(Request $request): RedirectResponse
    {
        $portal = $request->route('portal');
        if ($portal !== null && ! PortalAccess::enabled((string) $portal)) {
            return redirect()->route('login.'.$portal);
        }
        $credentials = $request->validate(['email' => ['required', 'email', 'max:190'], 'password' => ['required', 'string', 'max:255']]);
        $email = Str::lower(trim($credentials['email']));
        $key = 'login:'.hash('sha256', $email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Too many login attempts. Please wait before trying again.'])->onlyInput('email');
        }
        if (! Auth::attempt(['email' => $email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['email' => 'The supplied sign-in details could not be verified.'])->onlyInput('email');
        }
        if ($portal !== null) {
            $roles = $request->user()->getRoleNames();
            $isStaff = $roles->contains(fn ($role) => ! in_array($role, ['Student', 'Parent'], true));
            $belongs = $portal === 'parent' ? $roles->contains('Parent') : $isStaff;
            if (! $belongs) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['email' => $portal === 'parent'
                    ? 'This sign-in is for parents only. Staff should use the Staff Portal; students use the Student Portal.'
                    : 'This sign-in is for teachers and school staff only. Parents should use the Parent Portal; students use the Student Portal.'])->onlyInput('email');
            }
        }
        $blocked = PortalAccess::blockedMessage($request->user());
        if ($blocked !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => $blocked])->onlyInput('email');
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        AuditLog::record($request->user(), 'auth.login');
        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::record($request->user(), 'auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:190']]);
        Password::sendResetLink(['email' => Str::lower(trim($request->string('email')->toString()))]);
        return back()->with('status', 'If that account can receive reset messages, instructions will be sent to its email address.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'], 'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        $status = Password::reset(
            ['email' => Str::lower($data['email']), 'password' => $data['password'], 'password_confirmation' => $data['password_confirmation'], 'token' => $data['token']],
            function ($user, string $password): void {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
                AuditLog::record($user, 'auth.password_reset');
            },
        );
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password has been reset. You may sign in now.')
            : back()->withErrors(['email' => 'The password reset link is invalid or expired.']);
    }
}
