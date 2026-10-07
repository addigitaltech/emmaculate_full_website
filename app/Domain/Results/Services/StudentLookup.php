<?php

namespace App\Domain\Results\Services;

use App\Models\Student;
use Illuminate\Support\Str;

/** Finds an active student from an admission number and surname (case and spacing do not matter). */
final class StudentLookup
{
    public static function normaliseNumber(string $value): string
    {
        return Str::upper((string) preg_replace('/\s+/', '', trim($value)));
    }

    public static function normaliseName(string $value): string
    {
        return Str::upper(trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    public function find(string $number, string $surname): ?Student
    {
        $number = self::normaliseNumber($number);
        $surname = self::normaliseName($surname);
        if ($number === '' || $surname === '') {
            return null;
        }

        $student = Student::query()
            ->where('status', 'active')
            ->whereRaw("UPPER(REPLACE(student_number, ' ', '')) = ?", [$number])
            ->first();

        // Always compare something so a wrong number and a wrong surname take about the same time.
        $expected = $student ? self::normaliseName((string) $student->last_name) : self::normaliseName('no-such-student');

        return ($student && hash_equals($expected, $surname)) ? $student : null;
    }
}
