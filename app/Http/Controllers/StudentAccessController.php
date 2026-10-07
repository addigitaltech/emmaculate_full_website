<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Support\PortalAccess;
use App\Domain\Results\Services\StudentLookup;
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
 * Sign-in for students who have no email address: admission number + surname.
 * The first successful sign-in creates the student's account automatically.
 */
class StudentAccessController extends Controller
{
    public function login(Request $request, StudentLookup $lookup): RedirectResponse
    {
        $data = $request->validate([
            'admission_number' => ['required', 'string', 'max:60'],
            'surname' => ['required', 'string', 'max:80'],
        ]);

        $key = 'student-login:'.hash('sha256', StudentLookup::normaliseNumber($data['admission_number']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['admission_number' => 'Too many attempts. Please wait 15 minutes, or ask the school office for help.'])->onlyInput('admission_number');
        }
        if (! PortalAccess::studentEnabled()) {
            return back()->withErrors(['admission_number' => PortalAccess::suspendedMessage('student')])->onlyInput('admission_number');
        }

        $student = $lookup->find($data['admission_number'], $data['surname']);
        if (! $student) {
            RateLimiter::hit($key, 900);

            return back()->withErrors(['admission_number' => 'We could not verify those details. Check the admission number and type the surname in CAPITAL LETTERS.'])->onlyInput('admission_number');
        }

        $user = $this->accountFor($student);
        if (! $user) {
            return back()->withErrors(['admission_number' => 'This student record cannot sign in here. Please contact the school office.'])->onlyInput('admission_number');
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
                'email' => 'student-'.$fresh->id.'@students.emmaculateacademy.invalid',
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(48)),
            ])->save();
            $user->assignRole('Student');
            $fresh->forceFill(['user_id' => $user->id])->save();

            return $user;
        });
    }
}
