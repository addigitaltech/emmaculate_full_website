<?php

namespace Tests\Unit\Results;

use App\Domain\Results\Services\ResultCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResultCalculatorTest extends TestCase
{
    #[Test]
    public function it_preserves_the_source_grade_bands_at_each_boundary(): void
    {
        $calculator = new ResultCalculator();
        self::assertSame(['grade' => 'A', 'remark' => 'Excellent'], $calculator->gradeFor(70));
        self::assertSame(['grade' => 'B', 'remark' => 'Very Good'], $calculator->gradeFor(60));
        self::assertSame(['grade' => 'C', 'remark' => 'Good'], $calculator->gradeFor(50));
        self::assertSame(['grade' => 'D', 'remark' => 'Fair'], $calculator->gradeFor(45));
        self::assertSame(['grade' => 'E', 'remark' => 'Pass'], $calculator->gradeFor(40));
        self::assertSame(['grade' => 'F', 'remark' => 'Fail'], $calculator->gradeFor(39));
    }

    #[Test]
    public function it_calculates_totals_on_the_server_and_rejects_out_of_range_scores(): void
    {
        $calculator = new ResultCalculator();
        $result = $calculator->calculate(['ca1' => 31, 'ca2' => 0, 'ca3' => 0, 'exam' => 51]);
        self::assertSame(82.0, $result['total_score']);
        self::assertSame(82.0, $result['percentage']);
        self::assertSame('A', $result['grade']);

        $this->expectException(InvalidArgumentException::class);
        $calculator->calculate(['ca1' => 41, 'exam' => 60]);
    }

    #[Test]
    public function it_uses_competition_ranking_for_ties(): void
    {
        $calculator = new ResultCalculator();
        self::assertSame(1, $calculator->competitionRank(91, [91, 91, 80, 75]));
        self::assertSame(3, $calculator->competitionRank(80, [91, 91, 80, 75]));
    }

    #[Test]
    public function it_averages_only_saved_term_scores(): void
    {
        self::assertSame(
            ['cumulative_total' => 180.0, 'cumulative_average' => 90, 'cumulative_terms_count' => 2],
            (new ResultCalculator())->cumulativeAverage([90, null, 90]),
        );
    }
}
