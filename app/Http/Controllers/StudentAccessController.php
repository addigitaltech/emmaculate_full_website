<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Support\PortalAccess;
use App\Domain\Results\Services\StudentLookup;
use App\Domain\Results\Services\StudentPinService;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Sign-in for students who have no email address: Student ID + surname, plus a 6-digit PIN from the school
 * when the system setting "Require PIN to sign in" is on.
 * The first successful sign-in creates the student's account automatically.
 */
class StudentAccessController extends Controller
{
    public function login(Request $request, StudentLookup $lookup, StudentPinService $pins): RedirectResponse
    {
        $pinRequired = PortalAccess::studentPinRequired();
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:60'],
            'surname' => ['required', 'string', 'max:80'],
            \App\Support\Turnstile::FIELD => [new \App\Rules\TurnstileCaptcha('student_login')],
        ] + ($pinRequired ? ['pin' => ['required', 'string', 'regex:/^\s*\d{6}\s*$/']] : []), ['pin.regex' => 'Enter the 6-digit PIN from your PIN slip.']);

        $key = 'student-login:'.hash('sha256', StudentLookup::normaliseNumber($data['student_id']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['student_id' => 'Too many attempts. Please wait 15 minutes, or ask the school office for help.'])->onlyInput('student_id');
        }
        if (! PortalAccess::studentEnabled()) {
            return back()->withErrors(['student_id' => PortalAccess::suspendedMessage('student')])->onlyInput('student_id');
        }

        $student = $lookup->find($data['student_id'], $data['surname']);
        if (! $student) {
            RateLimiter::hit($key, 900);

            return back()->withErrors(['student_id' => 'We could not verify those details. Check the Student ID, the surname in CAPITAL LETTERS and the PIN.'])->onlyInput('student_id');
        }

        // The PIN is checked only while the school has "Require PIN to sign in" switched on in the system settings.
        $outcome = $pinRequired ? $pins->attempt($student, (string) ($data['pin'] ?? '')) : 'ok';
        if ($outcome === 'no_pin') {
            return back()->withErrors(['student_id' => 'No PIN has been issued for this student yet. Please ask the school office for your PIN slip.'])->onlyInput('student_id');
        }
        if ($outcome === 'locked') {
            return back()->withErrors(['student_id' => 'Sign-in is locked for a while after too many wrong PINs. Try again later, or ask the school office to reset your PIN.'])->onlyInput('student_id');
        }
        if ($outcome !== 'ok') {
            RateLimiter::hit($key, 900);

            return back()->withErrors(['student_id' => 'We could not verify those details. Check the Student ID, the surname in CAPITAL LETTERS and the PIN.'])->onlyInput('student_id');
        }

        $user = $this->accountFor($student);
        if (! $user) {
            return back()->withErrors(['student_id' => 'This student record cannot sign in here. Please contact the school office.'])->onlyInput('student_id');
        }

        RateLimiter::clear($key);
        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record($user, 'auth.student_login', $student);

        return redirect()->route('portal.dashboard');
    }

    /** The student's own account, created on first use. Accounts that carry any other role are refused. */
    private function accountFor(Student $student): ?User
    {
        if ($student->user_id) {
            $user = User::query()->find($student->user_id);
            if (! $user) {
                return null;
            }
            $roles = $user->getRoleNames();

            return ($roles->count() === 1 && $roles->first() === 'Student') ? $user : null;
        }

        return DB::transaction(function () use ($student): ?User {
            $fresh = Student::query()->lockForUpdate()->find($student->id);
            if (! $fresh) {
                return null;
            }
            if ($fresh->user_id) {
                return User::query()->find($fresh->user_id);
            }
            $user = new User();
            $user->forceFill([
                'name' => $fresh->fullName(),
                'email' => 'student-'.$fresh->id.'@students.invalid',
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(48)),
            ])->save();
            $user->assignRole('Student');
            $fresh->forceFill(['user_id' => $user->id])->save();

            return $user;
        });
    }
}
