<?php

namespace App\Domain\Identity;

use App\Models\ParentProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Lets an administrator set a teacher's or parent's sign-in password from their profile, and keeps the sign-in email in step. */
final class AccountPasswords
{
    public static function apply(Teacher|ParentProfile $profile, ?string $password, string $role): void
    {
        if (! filled($password)) {
            return;
        }

        $profile->refresh();
        $user = self::userFor($profile);
        if (! $user) {
            throw ValidationException::withMessages(['password' => 'Add a valid email address first; the sign-in account is created from it.']);
        }
        if (! $user->hasRole($role)) {
            throw ValidationException::withMessages(['password' => 'That email belongs to an account without the '.$role.' role, so its password cannot be changed here.']);
        }
        if ($user->hasRole('Super Admin') && ! auth()->user()?->hasRole('Super Admin')) {
            throw ValidationException::withMessages(['password' => 'Only a Super Admin can change a Super Admin password.']);
        }

        $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
    }

    /** When the profile email is changed, change the sign-in email too (unless another account already uses it). */
    public static function syncEmail(Teacher|ParentProfile $profile): void
    {
        $profile->refresh();
        $email = Str::lower(trim((string) $profile->email));
        $user = $profile->user_id ? User::query()->find($profile->user_id) : null;
        if (! $user || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || Str::lower((string) $user->email) === $email) {
            return;
        }
        if (User::query()->where('email', $email)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Another account already uses that email address.']);
        }
        $user->forceFill(['email' => $email])->save();
    }

    private static function userFor(Teacher|ParentProfile $profile): ?User
    {
        if ($profile->user_id) {
            return User::query()->find($profile->user_id);
        }
        $email = Str::lower(trim((string) $profile->email));

        return $email !== '' ? User::query()->where('email', $email)->first() : null;
    }
}
