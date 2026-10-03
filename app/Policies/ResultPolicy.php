<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\TeacherAssignment;
use App\Models\User;

class ResultPolicy
{
    public function view(User $user, Result $result): bool
    {
        if ($user->hasRole('Super Admin') || $user->can('manage results')) {
            return true;
        }
        if ($user->hasRole('Teacher')) {
            $teacherId = $user->teacherProfile()->value('id');
            return $teacherId && TeacherAssignment::query()->where('teacher_id', $teacherId)
                ->where('school_class_id', $result->school_class_id)
                ->where('subject_id', $result->subject_id)
                ->where(fn ($query) => $query->whereNull('arm_id')->orWhere('arm_id', $result->arm_id))
                ->exists();
        }
        if ($result->status !== 'published' || ! $result->published_at || $result->published_at->isFuture()) {
            return false;
        }
        if ($user->hasRole('Student')) {
            return (int) $user->studentProfile()->value('id') === (int) $result->student_id;
        }
        if ($user->hasRole('Parent')) {
            $parentId = $user->parentProfile()->value('id');
            return $parentId && $user->parentProfile()->first()?->students()->whereKey($result->student_id)->exists();
        }
        return false;
    }

    public function publish(User $user, Result $result): bool
    {
        return $result->status !== 'published'
            && ($user->hasRole('Super Admin') || $user->can('publish results'));
    }
}
