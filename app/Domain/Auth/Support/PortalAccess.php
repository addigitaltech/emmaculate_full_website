<?php

namespace App\Domain\Auth\Support;

use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use App\Models\User;

/**
 * The school decides, in the admin console, whether students and parents get portal accounts
 * and whether results can be looked up with an admission number and surname.
 * Staff accounts (teachers and administrators) are never affected by these switches.
 */
final class PortalAccess
{
    public static function studentEnabled(): bool
    {
        return (bool) SchoolSettings::current()->student_portal_enabled;
    }

    public static function parentEnabled(): bool
    {
        return (bool) SchoolSettings::current()->parent_portal_enabled;
    }

    public static function checkerEnabled(): bool
    {
        return (bool) SchoolSettings::current()->public_result_check_enabled;
    }

    /** Text shown when a student or parent is turned away because their portal is switched off. */
    public static function suspendedMessage(string $who): string
    {
        $message = 'The '.$who.' portal is currently switched off by the school.';

        return self::checkerEnabled()
            ? $message.' You can still check results with your admission number and surname.'
            : $message.' Please contact the school office.';
    }

    /** Null when the user may use the portal; otherwise the reason to show them. */
    public static function blockedMessage(User $user): ?string
    {
        $roles = $user->getRoleNames();
        if ($roles->contains(fn ($role) => ! in_array($role, ['Student', 'Parent'], true))) {
            return null;
        }
        if ($roles->contains('Student') && ! self::studentEnabled()) {
            return self::suspendedMessage('student');
        }
        if ($roles->contains('Parent') && ! self::parentEnabled()) {
            return self::suspendedMessage('parent');
        }

        return null;
    }

    /** True when the school gates results behind fees and this student still owes some. */
    public static function feesBlockResults(Student $student): bool
    {
        return SchoolSettings::current()->payment_gate_results
            && StudentFeeAssignment::query()->where('student_id', $student->id)->whereIn('status', ['unpaid', 'overdue'])->exists();
    }
}
