<?php

namespace App\Domain\Results\Services;

use App\Models\AcademicTerm;
use App\Models\AffectiveRating;
use App\Models\Result;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\TermRemark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the data for one student's report card for one term: subject rows, class statistics,
 * positions, behaviour ratings, remarks and the summary block shown at the bottom of the report.
 */
final class TermReportBuilder
{
    public function __construct(
        private readonly ResultCalculator $calculator,
        private readonly ResultSheetService $sheets,
    ) {
    }

    /** @param bool $includeUnpublished staff preview: pending results are included (never for students or parents) */
    public function build(Student $student, AcademicTerm $term, bool $includeUnpublished): array
    {
        $student->loadMissing(['schoolClass', 'arm', 'photo']);
        $term->loadMissing('session');
        $settings = SchoolSettings::current();
        $classId = $student->school_class_id;

        $population = Result::query()
            ->where('academic_session_id', $term->academic_session_id)
            ->where('academic_term_id', $term->id)
            ->where('school_class_id', $classId)
            ->where('is_offered', true)
            ->when($includeUnpublished, fn ($query) => $query->whereIn('status', ['pending', 'published']), fn ($query) => $query->published())
            ->get();

        $mine = $population->where('student_id', $student->id)->values();
        $subjects = $mine->isEmpty()
            ? collect()
            : \App\Models\Subject::query()->whereIn('id', $mine->pluck('subject_id'))->orderBy('name')->get()->keyBy('id');

        $rows = $mine->sortBy(fn (Result $result) => $subjects[$result->subject_id]->name ?? '')->values()->map(function (Result $result) use ($population, $subjects, $term, $classId): array {
            $scores = $population->where('subject_id', $result->subject_id)->pluck('total_score')->map(fn ($value) => (float) $value)->values();
            $score = (float) $result->total_score;
            $maxima = $this->sheets->maxima((int) $term->academic_session_id, (int) $term->id, $classId, (int) $result->subject_id);

            return [
                'subject' => $subjects[$result->subject_id]->name ?? '—',
                'ca1' => (float) $result->ca1_score,
                'ca2' => (float) $result->ca2_score,
                'ca3' => (float) $result->ca3_score,
                'exam' => (float) $result->exam_score,
                'total' => $score,
                'grade' => $result->grade,
                'remark' => $result->remark,
                'average' => $scores->isNotEmpty() ? round($scores->avg(), 1) : 0.0,
                'highest' => $scores->isNotEmpty() ? (float) $scores->max() : 0.0,
                'lowest' => $scores->isNotEmpty() ? (float) $scores->min() : 0.0,
                'position' => 1 + $scores->filter(fn ($other) => $other > $score)->count(),
                'population' => $scores->count(),
                'maxima' => $maxima,
                'status' => $result->status,
            ];
        })->all();

        $classAverages = $this->averages($population);
        $armPopulation = $student->arm_id ? $population->where('arm_id', $student->arm_id)->values() : $population;
        $armAverages = $this->averages($armPopulation);
        $bands = $this->sheets->gradeBands();

        $totalScore = collect($rows)->sum('total');
        $count = count($rows);
        $average = $count ? round($totalScore / $count, 2) : 0.0;
        $passed = collect($rows)->filter(fn (array $row) => $row['total'] >= (float) $settings->pass_percentage)->count();

        $remarks = TermRemark::query()
            ->where('student_id', $student->id)
            ->where('academic_session_id', $term->academic_session_id)
            ->where('academic_term_id', $term->id)
            ->first();
        $ratings = AffectiveRating::query()->with('trait')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $term->academic_session_id)
            ->where('academic_term_id', $term->id)
            ->get()
            ->sortBy(fn (AffectiveRating $rating) => $rating->trait?->sort_order ?? 0)
            ->values();

        $logoPath = $settings->logo_path ? Storage::disk('public')->path($settings->logo_path) : null;
        if (! $logoPath || ! is_file($logoPath)) {
            $logoPath = null;
        }
        $photoPath = $student->photo?->path ? Storage::disk('public')->path($student->photo->path) : null;
        if (! $photoPath || ! is_file($photoPath)) {
            $photoPath = null;
        }

        return [
            'student' => $student,
            'term' => $term,
            'settings' => $settings,
            'rows' => $rows,
            'bands' => $bands,
            'includeUnpublished' => $includeUnpublished,
            'summary' => [
                'class_total' => $classAverages->count(),
                'class_position' => $this->rank($classAverages, (int) $student->id),
                'arm_total' => $armAverages->count(),
                'arm_position' => $student->arm_id ? $this->rank($armAverages, (int) $student->id) : null,
                'total_score' => round($totalScore, 2),
                'average' => $average,
                'overall' => $this->calculator->gradeFor($average, $bands),
                'offered' => $count,
                'passed' => $passed,
                'failed' => $count - $passed,
                'pass_mark' => (float) $settings->pass_percentage,
            ],
            'ratings' => $ratings,
            'teacherRemark' => $remarks?->teacher_remark,
            'principalRemark' => $remarks?->principal_remark,
            'logoPath' => $logoPath,
            'photoPath' => $photoPath,
        ];
    }

    /** @return Collection<int, float> student id => average total score across offered subjects */
    private function averages(Collection $rows): Collection
    {
        return $rows->groupBy('student_id')->map(fn (Collection $studentRows): float => (float) $studentRows->avg(fn (Result $result) => (float) $result->total_score));
    }

    private function rank(Collection $averages, int $studentId): ?int
    {
        if (! $averages->has($studentId)) {
            return null;
        }
        $score = (float) $averages->get($studentId);

        return 1 + $averages->filter(fn ($other) => (float) $other > $score)->count();
    }
}
