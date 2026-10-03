<?php

namespace Database\Seeders;

use App\Domain\Results\Services\ResultCalculator;
use App\Models\GradeBand;
use Illuminate\Database\Seeder;

class AcademicDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ResultCalculator::DEFAULT_GRADE_BANDS as $index => $band) {
            GradeBand::query()->updateOrCreate(
                ['grade' => $band['grade']],
                $band + ['sort_order' => $index],
            );
        }
    }
}
