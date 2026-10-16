<?php

namespace App\Domain\Identity;

use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Keeps logins and school records in step:
 *  - every student gets a login account (role Student) the moment the student is added, uploaded or imported;
 *  - a teacher or parent with an email gets an account with the right role;
 *  - a user created with role Teacher, Parent or Student automatically gets the matching school record
 *    (so teachers can be given subjects, and students appear under Students).
 * Everything here is safe to run more than once.
 */
final class IdentitySync
{
    public static function forStudent(Student $student): void
    {
        if ($student->user_id) {
            self::renameUser((int) $student->user_id, $student->fullName());

            return;
        }

        DB::transaction(function () use ($student): void {
            $fresh = Student::query()->lockForUpdate()->find($student->id);
            if (! $fresh || $fresh->user_id) {
                return;
            }
            $user = self::makeUser($fresh->fullName(), 'student-'.$fresh->id.'@students.emmaculateacademy.invalid', 'Student');
            $fresh->forceFill(['user_id' => $user->id])->saveQuietly();
            $student->user_id = $user->id;
        });
    }

    public static function forTeacher(Teacher $teacher): void
    {
        if ($teacher->user_id || ! filled($teacher->email)) {
            return;
        }
        self::linkByEmail($teacher, (string) $teacher->email, trim($teacher->first_name.' '.$teacher->last_name), 'Teacher');
    }

    public static function forParent(ParentProfile $parent): void
    {
        if ($parent->user_id || ! filled($parent->email)) {
            return;
        }
        self::linkByEmail($parent, (string) $parent->email, (string) $parent->full_name, 'Parent');
    }

    /** Called after an admin saves a user: make sure the matching school record exists. */
    public static function forUser(User $user): void
    {
        [$first, $last] = self::splitName($user->name);

        if ($user->hasRole('Teacher') && ! Teacher::query()->where('user_id', $user->id)->exists()) {
            Teacher::query()->create(['user_id' => $user->id, 'first_name' => $first, 'last_name' => $last, 'email' => $user->email, 'status' => 'active']);
        }
        if ($user->hasRole('Parent') && ! ParentProfile::query()->where('user_id', $user->id)->exists()) {
            ParentProfile::query()->create(['user_id' => $user->id, 'full_name' => $user->name, 'email' => $user->email]);
        }
        if ($user->hasRole('Student') && ! Student::query()->where('user_id', $user->id)->exists()) {
            // The admission number and class are completed later under People > Students.
            Student::query()->create([
                'user_id' => $user->id, 'student_number' => 'STU-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                'first_name' => $first, 'last_name' => $last, 'status' => 'active',
            ]);
        }
    }

    /** @return array{students: int, teachers: int, parents: int, users: int} how many records were created or linked */
    public static function backfill(): array
    {
        $done = ['students' => 0, 'teachers' => 0, 'parents' => 0, 'users' => 0];

        Student::query()->whereNull('user_id')->orderBy('id')->chunkById(200, function ($students) use (&$done): void {
            foreach ($students as $student) {
                self::forStudent($student);
                $done['students']++;
            }
        });
        foreach (Teacher::query()->whereNull('user_id')->whereNotNull('email')->get() as $teacher) {
            self::forTeacher($teacher);
            $done['teachers']++;
        }
        foreach (ParentProfile::query()->whereNull('user_id')->whereNotNull('email')->get() as $parent) {
            self::forParent($parent);
            $done['parents']++;
        }
        foreach (User::role(['Teacher', 'Parent', 'Student'])->get() as $user) {
            self::forUser($user);
            $done['users']++;
        }

        return $done;
    }

    private static function renameUser(int $userId, string $name): void
    {
        User::query()->whereKey($userId)->where('name', '!=', $name)->update(['name' => $name]);
    }

    private static function linkByEmail(Teacher|ParentProfile $record, string $email, string $name, string $role): void
    {
        $email = Str::lower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $user = User::query()->where('email', $email)->first() ?? self::makeUser($name !== '' ? $name : $email, $email, $role);
        if (! $user->hasRole($role)) {
            self::ensureRole($role);
        $user->assignRole($role);
        }
        $record->forceFill(['user_id' => $user->id])->saveQuietly();
    }

    /** A login account nobody can sign in to by password until it is reset or, for students, used with the admission number. */
    private static function makeUser(string $name, string $email, string $role): User
    {
        $user = new User();
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            // Low cost on purpose: the value is random and never used, and thousands of students may be imported at once.
            'password' => password_hash(Str::random(40), PASSWORD_BCRYPT, ['cost' => 4]),
        ])->save();
        self::ensureRole($role);
        $user->assignRole($role);

        return $user;
    }

    private static function ensureRole(string $role): void
    {
        Role::findOrCreate($role, 'web');
    }

    /** @return array{0: string, 1: string} first name and surname (a single word is used for both) */
    private static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = $parts[0] ?? $name;
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $first;

        return [$first, $last];
    }
}
