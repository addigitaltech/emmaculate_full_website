<?php

namespace App\Domain\Auth\Support;

use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use App\Models\User;

/**
 * The Super Admin decides which portals exist. A portal that is switched off disappears from the public
 * website (menus, home page, footer) and its login page shows a "switched off" notice.
 * Administrators can always use /admin.
 */
final class PortalAccess
{
    public const TITLES = ['student' => 'student', 'parent' => 'parent', 'staff' => 'staff'];

    public static function studentEnabled(): bool
    {
        return (bool) SchoolSettings::current()->student_portal_enabled;
    }

    public static function parentEnabled(): bool
    {
        return (bool) SchoolSettings::current()->parent_portal_enabled;
    }

    public static function staffEnabled(): bool
    {
        return (bool) SchoolSettings::current()->staff_portal_enabled;
    }

    public static function admissionEnabled(): bool
    {
        return (bool) SchoolSettings::current()->admission_portal_enabled;
    }

    public static function checkerEnabled(): bool
    {
        return (bool) SchoolSettings::current()->public_result_check_enabled;
    }

    /** @param  string  $portal  student, parent or staff */
    public static function enabled(string $portal): bool
    {
        return match ($portal) {
            'student' => self::studentEnabled(),
            'parent' => self::parentEnabled(),
            'staff' => self::staffEnabled(),
            default => false,
        };
    }

    /** Text shown when a person is turned away because their portal is switched off. */
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
        // Administrators and results staff are never locked out by these switches.
        if ($roles->contains(fn ($role) => ! in_array($role, ['Student', 'Parent', 'Teacher'], true))) {
            return null;
        }
        if ($roles->contains('Teacher') && ! self::staffEnabled()) {
            return self::suspendedMessage('staff');
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
