<?php

namespace App\Domain\Results\Services;

use InvalidArgumentException;

final class ResultCalculator
{
    public const DEFAULT_MAXIMA = ['ca1' => 40, 'ca2' => 0, 'ca3' => 0, 'exam' => 60];

    public const DEFAULT_GRADE_BANDS = [
        ['min_score' => 70, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent'],
        ['min_score' => 60, 'max_score' => 69, 'grade' => 'B', 'remark' => 'Very Good'],
        ['min_score' => 50, 'max_score' => 59, 'grade' => 'C', 'remark' => 'Good'],
        ['min_score' => 45, 'max_score' => 49, 'grade' => 'D', 'remark' => 'Fair'],
        ['min_score' => 40, 'max_score' => 44, 'grade' => 'E', 'remark' => 'Pass'],
        ['min_score' => 0, 'max_score' => 39, 'grade' => 'F', 'remark' => 'Fail'],
    ];

    /** @param array<string, int|float|null> $scores @param array<string, int|float> $maxima */
    public function calculate(array $scores, array $maxima = self::DEFAULT_MAXIMA, ?array $bands = null): array
    {
        $total = 0.0;
        $maximum = 0.0;
        $normalized = [];
        foreach (array_keys(self::DEFAULT_MAXIMA) as $component) {
            $limit = (float) ($maxima[$component] ?? 0);
            $raw = $scores[$component] ?? 0;
            if (! is_numeric($raw) || ! is_finite((float) $raw) || $limit < 0) {
                throw new InvalidArgumentException("Invalid score value for {$component}.");
            }
            $value = (float) $raw;
            if ($value < 0 || $value > $limit) {
                throw new InvalidArgumentException("{$component} score must be between 0 and {$limit}.");
            }
            $normalized[$component] = round($value, 2);
            $total += $value;
            $maximum += $limit;
        }

        $percentage = $maximum > 0 ? round(($total / $maximum) * 100, 2) : 0.0;
        $grade = $this->gradeFor($percentage, $bands);
        return [
            'scores' => $normalized,
            'total_score' => round($total, 2),
            'maximum_score' => round($maximum, 2),
            'percentage' => $percentage,
            'grade' => $grade['grade'],
            'remark' => $grade['remark'],
        ];
    }

    public function gradeFor(float $score, ?array $bands = null): array
    {
        $effective = $bands ?: self::DEFAULT_GRADE_BANDS;
        foreach ($effective as $band) {
            if ($score >= (float) $band['min_score'] && $score <= (float) $band['max_score']) {
                return ['grade' => (string) $band['grade'], 'remark' => (string) $band['remark']];
            }
        }
        return $score >= 70
            ? ['grade' => 'A', 'remark' => 'Excellent']
            : ['grade' => 'F', 'remark' => 'Fail'];
    }

    /** Competition rank: tied values share count(scores greater than this score) + 1. */
    public function competitionRank(float $score, array $allScores): int
    {
        return count(array_filter($allScores, static fn ($other): bool => is_numeric($other) && (float) $other > $score)) + 1;
    }

    /** Only supplied saved/published term scores count; absent terms are not treated as zero. */
    public function cumulativeAverage(array $termScores): array
    {
        $values = array_values(array_filter($termScores, static fn ($score): bool => $score !== null && is_numeric($score)));
        $count = count($values);
        $total = array_sum(array_map(static fn ($score): float => (float) $score, $values));
        return ['cumulative_total' => round($total, 2), 'cumulative_average' => $count ? (int) round($total / $count) : 0, 'cumulative_terms_count' => $count];
    }
}
