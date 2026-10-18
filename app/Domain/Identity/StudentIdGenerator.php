<?php

namespace App\Domain\Identity;

use App\Models\AcademicSession;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentIdCounter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Automatic Student IDs: PREFIX/SESSION/NUMBER, e.g. EAC/2627/0001.
 *  - PREFIX is the school's own code, editable in the school information settings (default EAC);
 *  - SESSION is the short form of the academic session the student is entered in (2026/2027 becomes 2627);
 *  - NUMBER is the next running number for that prefix and session, padded to four digits.
 * An ID is handed out the moment a student record is created, never typed in, and never repeated.
 */
final class StudentIdGenerator
{
    public static function prefix(): string
    {
        $prefix = Str::upper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) SchoolSettings::current()->student_id_prefix));

        return $prefix !== '' ? Str::substr($prefix, 0, 12) : 'STU';
    }

    /** 2026/2027 -> 2627. Without a usable session name it follows the Nigerian school year (starting September). */
    public static function sessionCode(?AcademicSession $session = null): string
    {
        $session ??= AcademicSession::query()->where('is_active', true)->orderByDesc('id')->first() ?? AcademicSession::query()->orderByDesc('id')->first();
        $name = (string) ($session?->name ?? '');

        if (preg_match('/(\d{4})\D+(\d{4})/', $name, $match)) {
            return substr($match[1], 2, 2).substr($match[2], 2, 2);
        }
        if (preg_match('/(\d{4})/', $name, $match)) {
            $start = (int) $match[1];

            return substr((string) $start, 2, 2).substr((string) ($start + 1), 2, 2);
        }

        $start = (int) now()->format('n') >= 9 ? (int) now()->format('Y') : (int) now()->format('Y') - 1;

        return substr((string) $start, 2, 2).substr((string) ($start + 1), 2, 2);
    }

    public static function next(?AcademicSession $session = null): string
    {
        $prefix = self::prefix();
        $code = self::sessionCode($session);

        // Make sure the counter row exists before locking it (outside the transaction so a race on the very
        // first student of a session cannot poison it).
        try {
            StudentIdCounter::query()->firstOrCreate(['prefix' => $prefix, 'session_code' => $code], ['last_number' => 0]);
        } catch (QueryException) {
            // Another request created it a moment earlier; that is fine.
        }

        return DB::transaction(function () use ($prefix, $code): string {
            $counter = StudentIdCounter::query()->where('prefix', $prefix)->where('session_code', $code)->lockForUpdate()->firstOrFail();
            do {
                $counter->last_number = (int) $counter->last_number + 1;
                $id = sprintf('%s/%s/%04d', $prefix, $code, $counter->last_number);
            } while (Student::query()->where('student_number', $id)->exists());
            $counter->save();

            return $id;
        });
    }

    public static function isStandard(?string $value): bool
    {
        return (bool) preg_match('#^[A-Z0-9]{1,12}/\d{4}/\d{4,}$#', (string) $value);
    }

    /** How many students still carry an older, non-standard number. */
    public static function legacyCount(): int
    {
        return Student::query()->get(['id', 'student_number'])->filter(fn (Student $student) => ! self::isStandard($student->student_number))->count();
    }

    /** Give every student with an older number a proper Student ID (oldest records first). Returns how many were converted. */
    public static function convertLegacy(): int
    {
        $converted = 0;
        Student::query()->orderBy('id')->chunkById(200, function ($students) use (&$converted): void {
            foreach ($students as $student) {
                if (! self::isStandard($student->student_number)) {
                    $student->forceFill(['student_number' => self::next()])->saveQuietly();
                    $converted++;
                }
            }
        });

        return $converted;
    }
}
