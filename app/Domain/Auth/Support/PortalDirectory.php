<?php

namespace App\Domain\Auth\Support;

use App\Models\SchoolSettings;

/** The portals a visitor can currently see. Anything switched off is simply not in the list. */
final class PortalDirectory
{
    /** @return array<int, array{key: string, title: string, description: string, url: string}> */
    public static function enabled(SchoolSettings $settings): array
    {
        $all = [
            ['key' => 'results', 'title' => 'Results Portal', 'description' => 'Check your results online', 'url' => '/check-result', 'on' => (bool) $settings->public_result_check_enabled],
            ['key' => 'admission', 'title' => 'Admission Portal', 'description' => 'Apply for admission online', 'url' => '/admissions', 'on' => (bool) $settings->admission_portal_enabled],
            ['key' => 'students', 'title' => 'Student Portal', 'description' => 'Access student resources', 'url' => '/student/login', 'on' => (bool) $settings->student_portal_enabled],
            ['key' => 'staff', 'title' => 'Staff Portal', 'description' => 'Access staff resources', 'url' => '/staff/login', 'on' => (bool) $settings->staff_portal_enabled],
            ['key' => 'parents', 'title' => 'Parent Portal', 'description' => 'Access parent resources', 'url' => '/parent/login', 'on' => (bool) $settings->parent_portal_enabled],
        ];

        $visible = [];
        foreach ($all as $portal) {
            if ($portal['on']) {
                unset($portal['on']);
                $visible[] = $portal;
            }
        }

        return $visible;
    }
}
