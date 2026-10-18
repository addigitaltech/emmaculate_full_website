<?php

namespace App\Domain\Results\Services;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Six-digit student PINs. Only a hash is stored, so a PIN can be shown exactly once (on the printed slip).
 * Wrong guesses are counted per student, whatever IP address they come from: every 5th wrong PIN locks the
 * student's sign-in for 15 minutes, then 30, 60 ... up to 24 hours, until a correct PIN or a PIN reset.
 */
final class StudentPinService
{
    public const ATTEMPTS_PER_LOCK = 5;

    public const FIRST_LOCK_MINUTES = 15;

    public const MAX_LOCK_MINUTES = 1440;

    /** Create a new PIN for the student (the old one stops working) and return it. Show it once, never store it. */
    public function issue(Student $student): string
    {
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // The caller may hold a stale model instance whose lock fields predate a failed attempt.
        // Refresh before saving so issuing a replacement PIN always clears the persisted lock.
        $student->refresh();
        $student->forceFill([
            'pin_hash' => Hash::make($pin),
            'pin_set_at' => now(),
            'pin_failed_attempts' => 0,
            'pin_locked_until' => null,
        ])->save();

        return $pin;
    }

    /** @return 'ok'|'wrong'|'locked'|'no_pin' */
    public function attempt(Student $student, string $pin): string
    {
        $pin = preg_replace('/\s+/', '', $pin) ?? '';

        return DB::transaction(function () use ($student, $pin): string {
            $row = Student::query()->lockForUpdate()->find($student->id);
            if (! $row || ! $row->pin_hash) {
                return 'no_pin';
            }
            if ($row->pin_locked_until && $row->pin_locked_until->isFuture()) {
                return 'locked';
            }
            if (Hash::check($pin, $row->pin_hash)) {
                $row->forceFill(['pin_failed_attempts' => 0, 'pin_locked_until' => null])->save();

                return 'ok';
            }

            $attempts = (int) $row->pin_failed_attempts + 1;
            $values = ['pin_failed_attempts' => $attempts];
            $locks = $attempts % self::ATTEMPTS_PER_LOCK === 0;
            if ($locks) {
                $minutes = min(self::MAX_LOCK_MINUTES, self::FIRST_LOCK_MINUTES * (2 ** (intdiv($attempts, self::ATTEMPTS_PER_LOCK) - 1)));
                $values['pin_locked_until'] = now()->addMinutes($minutes);
            }
            $row->forceFill($values)->save();

            return $locks ? 'locked' : 'wrong';
        });
    }
}
