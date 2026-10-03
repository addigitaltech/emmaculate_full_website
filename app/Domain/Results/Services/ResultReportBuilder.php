<?php

namespace App\Domain\Results\Services;

use App\Models\AffectiveRating;
use App\Models\AssessmentConfig;
use App\Models\GradeBand;
use App\Models\Result;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\TermRemark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class ResultReportBuilder
{
    public function __construct(private readonly ResultCalculator $calculator)
    {
    }

    public function build(Result $anchor): array
    {
        $anchor->loadMissing(['student.schoolClass', 'student.arm', 'student.photo', 'subject', 'academicSession', 'academicTerm']);
        $student = $anchor->student;
        abort_unless($student, 404);
        $settings = SchoolSettings::current();
        $sessionId = $anchor->academic_session_id;
        $termId = $anchor->academic_term_id;

        $results = Result::published()->where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)->where('academic_term_id', $termId)
            ->with('subject')->orderBy('subject_id')->get();
        $offered = $results->where('is_offered', true)->values();
        $classId = $student->school_class_id;
        $classRows = $classId ? Result::published()->where('school_class_id', $classId)
            ->where('academic_session_id', $sessionId)->where('academic_term_id', $termId)
            ->with('subject')->get()->where('is_offered', true)->values() : collect();
        $armRows = $student->arm_id ? $classRows->where('arm_id', $student->arm_id)->values() : $classRows;

        $classMetrics = $this->studentAverages($classRows);
        $armMetrics = $this->studentAverages($armRows);
        $studentId = (int) $student->id;
        $classRank = $this->rank($classMetrics, $studentId);
        $armRank = $student->arm_id ? $this->rank($armMetrics, $studentId) : null;
        $studentCount = $classMetrics->count();
        $armCount = $armMetrics->count();

        $subjectStats = $offered->map(function (Result $result) use ($classRows): array {
            $population = $classRows->where('subject_id', $result->subject_id)->pluck('total_score')->map(fn ($score) => (float) $score)->values();
            $score = (float) $result->total_score;
            return [
                'subject_id' => $result->subject_id,
                'subject_name' => $result->subject?->name ?? '—',
                'score' => $score,
                'class_average' => $population->isNotEmpty() ? round($population->avg(), 2) : 0,
                'highest' => $population->isNotEmpty() ? $population->max() : 0,
                'lowest' => $population->isNotEmpty() ? $population->min() : 0,
                'position' => 1 + $population->filter(fn ($other) => (float) $other > $score)->count(),
                'population' => $population->count(),
            ];
        })->keyBy('subject_id');

        $sessionResults = Result::published()->where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)->with('subject')->get();
        $cumulative = $sessionResults->where('is_offered', true)->groupBy('subject_id')->map(function (Collection $rows): array {
            $scores = $rows->pluck('total_score')->map(fn ($score) => (float) $score);
            return ['total' => round($scores->sum(), 2), 'average' => (int) round($scores->avg()), 'terms' => $scores->count()];
        });

        $termRemark = TermRemark::query()->where('student_id', $student->id)->where('academic_session_id', $sessionId)->where('academic_term_id', $termId)->first();
        $ratings = AffectiveRating::query()->with('trait')->where('student_id', $student->id)->where('academic_session_id', $sessionId)->where('academic_term_id', $termId)->get()->sortBy(fn ($rating) => $rating->trait?->sort_order ?? 0)->values();
        $bands = $this->gradeBands();
        $assessment = AssessmentConfig::query()->where('academic_session_id', $sessionId)->where('academic_term_id', $termId)->where('school_class_id', $classId)->where('subject_id', $anchor->subject_id)->first();
        $maxima = $assessment?->maxima() ?? ['ca1' => $settings->ca1_max_score, 'ca2' => $settings->ca2_max_score, 'ca3' => $settings->ca3_max_score, 'exam' => $settings->exam_max_score];
        $totalMax = array_sum($maxima);
        $total = $offered->sum(fn (Result $result) => (float) $result->total_score);
        $average = $offered->isNotEmpty() ? (int) round($total / $offered->count()) : 0;
        $overallGrade = $this->calculator->gradeFor((float) $average, $bands);
        $passCount = $offered->filter(fn (Result $result) => $totalMax > 0 && ((float) $result->total_score / $totalMax) * 100 >= $settings->pass_percentage)->count();

        $logoPath = $settings->logo_path ? Storage::disk('public')->path($settings->logo_path) : null;
        if (! $logoPath || ! is_file($logoPath)) {
            $logoPath = null;
        }

        return compact('anchor', 'student', 'settings', 'results', 'offered', 'classRows', 'classRank', 'armRank', 'studentCount', 'armCount', 'subjectStats', 'cumulative', 'termRemark', 'ratings', 'bands', 'maxima', 'totalMax', 'total', 'average', 'overallGrade', 'passCount', 'logoPath');
    }

    private function studentAverages(Collection $rows): Collection
    {
        return $rows->groupBy('student_id')->map(function (Collection $studentRows): float {
            return (float) $studentRows->avg(fn (Result $result) => (float) $result->total_score);
        });
    }

    private function rank(Collection $metrics, int $studentId): ?int
    {
        if (! $metrics->has($studentId)) {
            return null;
        }
        $score = (float) $metrics->get($studentId);
        return 1 + $metrics->filter(fn ($other) => (float) $other > $score)->count();
    }

    private function gradeBands(): array
    {
        $bands = GradeBand::query()->orderBy('min_score')->get()->map(fn (GradeBand $band) => [
            'min_score' => $band->min_score, 'max_score' => $band->max_score,
            'grade' => $band->grade, 'remark' => $band->remark,
        ])->all();
        if (! $bands) {
            return ResultCalculator::DEFAULT_GRADE_BANDS;
        }
        $next = 0;
        foreach ($bands as $band) {
            if ($band['min_score'] !== $next || $band['max_score'] < $band['min_score']) {
                throw ValidationException::withMessages(['grade_bands' => 'The configured grade bands must cover every score from 0 to 100 without gaps or overlaps.']);
            }
            $next = $band['max_score'] + 1;
        }
        if ($next !== 101) {
            throw ValidationException::withMessages(['grade_bands' => 'The configured grade bands must cover every score from 0 to 100.']);
        }
        return $bands;
    }
}
