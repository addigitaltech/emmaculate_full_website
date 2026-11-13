<?php

namespace Tests\Feature;

use App\Domain\Results\Services\ResultSheetService;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubjectMultiClassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function one_subject_can_be_assigned_to_any_number_of_classes(): void
    {
        $classes = collect(range(1, 5))->map(fn (int $number) => SchoolClass::query()->create(['name' => 'Multi '.$number, 'is_active' => true]));
        $subject = Subject::query()->create(['code' => 'MULTI-101', 'name' => 'Shared Mathematics', 'status' => 'active']);
        $subject->schoolClasses()->sync($classes->pluck('id')->all());

        self::assertSame($classes->pluck('id')->sort()->values()->all(), $subject->fresh()->schoolClasses->pluck('id')->sort()->values()->all());
        foreach ($classes as $class) {
            self::assertTrue($subject->fresh()->appliesToClass((int) $class->id));
        }

        $subject->schoolClasses()->sync($classes->slice(1)->pluck('id')->all());
        self::assertFalse($subject->fresh()->appliesToClass((int) $classes->first()->id));
        self::assertTrue($subject->fresh()->appliesToClass((int) $classes->last()->id));
    }

    #[Test]
    public function a_multi_class_subject_can_be_assigned_to_a_teacher_in_each_class(): void
    {
        $classes = collect(range(1, 2))->map(fn (int $number) => SchoolClass::query()->create(['name' => 'Teacher Class '.$number, 'is_active' => true]));
        $subject = Subject::query()->create(['code' => 'MULTI-201', 'name' => 'Shared English', 'status' => 'active']);
        $subject->schoolClasses()->sync($classes->pluck('id')->all());
        $teacher = Teacher::query()->create(['first_name' => 'Subject', 'last_name' => 'Teacher', 'status' => 'active']);

        foreach ($classes as $class) {
            TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'school_class_id' => $class->id, 'subject_id' => $subject->id]);
        }

        self::assertSame(2, TeacherAssignment::query()->where('subject_id', $subject->id)->count());
    }
}
