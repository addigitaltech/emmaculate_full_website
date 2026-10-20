<?php

namespace Tests\Feature;

use App\Domain\Results\Services\StudentCsvImporter;
use App\Domain\Results\Services\StudentTemplateGenerator;
use App\Models\Arm;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** The generated upload sheet covers every class and arm and imports cleanly once filled in. */
class StudentUploadSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $jss1 = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $ss1 = SchoolClass::query()->create(['name' => 'SS1', 'is_active' => true]);
        $nursery = SchoolClass::query()->create(['name' => 'Nursery', 'is_active' => true]);
        $red = Arm::query()->create(['name' => 'Red']);
        $blue = Arm::query()->create(['name' => 'Blue']);
        $jss1->arms()->attach([$red->id, $blue->id]);
        $ss1->arms()->attach([$red->id]);
    }

    private function rows(string $csv): array
    {
        $lines = array_filter(preg_split('/\r?\n/', ltrim($csv, "\xEF\xBB\xBF")));

        return array_map('str_getcsv', $lines);
    }

    #[Test]
    public function the_sheet_has_a_block_of_lines_for_every_class_and_arm(): void
    {
        $rows = $this->rows(app(StudentTemplateGenerator::class)->csv(null, 3));

        $this->assertSame(StudentTemplateGenerator::HEADERS, $rows[0]);
        // JSS1 Red + JSS1 Blue + Nursery (no arms) + SS1 Red = 4 blocks of 3 lines
        $this->assertCount(1 + 4 * 3, $rows);
        $pairs = array_count_values(array_map(fn ($row) => $row[6].'|'.$row[7], array_slice($rows, 1)));
        $this->assertSame(['JSS1|Blue' => 3, 'JSS1|Red' => 3, 'Nursery|' => 3, 'SS1|Red' => 3], $pairs);
    }

    #[Test]
    public function the_sheet_can_be_limited_to_chosen_classes(): void
    {
        $ss1 = SchoolClass::query()->where('name', 'SS1')->value('id');

        $rows = $this->rows(app(StudentTemplateGenerator::class)->csv([$ss1], 2));

        $this->assertCount(1 + 2, $rows);
    }

    #[Test]
    public function a_sheet_larger_than_one_file_can_hold_is_refused(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            SchoolClass::query()->create(['name' => 'Extra '.$i, 'is_active' => true]);
        }
        $this->expectException(ValidationException::class);

        app(StudentTemplateGenerator::class)->csv(null, 200); // 14 blocks x 200 lines is more than one file can hold
    }

    #[Test]
    public function a_filled_in_sheet_imports_and_the_empty_lines_are_ignored(): void
    {
        $csv = app(StudentTemplateGenerator::class)->csv(null, 3);
        $lines = preg_split('/\r?\n/', trim($csv));
        // Fill in two students on the first two JSS1 Blue lines (blocks are sorted by class then arm).
        $lines[1] = ',Ada,Okoro,,Female,2014-03-17,JSS1,Blue,active';
        $lines[2] = ',Bola,Ajayi,,Male,2013-08-12,JSS1,Blue,active';
        $path = tempnam(sys_get_temp_dir(), 'sheet');
        file_put_contents($path, implode("\n", $lines)."\n");

        $result = app(StudentCsvImporter::class)->import($path);

        $this->assertSame(2, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertSame(2, Student::query()->count());
        $this->assertSame('Blue', Student::query()->where('first_name', 'Ada')->first()->arm->name);
    }
}
