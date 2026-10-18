<?php

namespace Tests\Feature;

use App\Domain\Results\Services\StudentCsvImporter;
use App\Models\Arm;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentCsvImporterTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'students');
        file_put_contents($path, $body);

        return $path;
    }

    #[Test]
    public function it_creates_students_with_automatic_ids_updates_by_student_id_and_reports_bad_rows(): void
    {
        $class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $arm = Arm::query()->create(['name' => 'R']);
        $class->arms()->attach($arm->id);

        $path = $this->csv("first_name,last_name,gender,date_of_birth,class,arm\n"
            ."Ada,Okoro,female,2014-03-17,JSS1,R\n"
            ."Bola,Ajayi,male,12/08/2013,JSS1,\n"
            ."Chi,Eze,male,2013-01-01,SS9,\n"
            ."NoLast,,male,,,\n");

        $result = app(StudentCsvImporter::class)->import($path);

        $this->assertSame(2, $result['created']);
        $this->assertCount(2, $result['errors']);
        $ada = Student::query()->where('first_name', 'Ada')->firstOrFail();
        $bola = Student::query()->where('first_name', 'Bola')->firstOrFail();
        $this->assertMatchesRegularExpression('#^[A-Z0-9]+/\d{4}/\d{4}$#', $ada->student_number);
        $this->assertNotSame($ada->student_number, $bola->student_number);
        $this->assertSame('Female', $ada->gender);
        $this->assertSame($class->id, $bola->school_class_id);

        $again = app(StudentCsvImporter::class)->import($this->csv("student_id,first_name,last_name\n{$ada->student_number},Adaeze,Okoro\n"));
        $this->assertSame(1, $again['updated']);
        $this->assertSame('Adaeze', $ada->fresh()->first_name);
        $this->assertSame(2, Student::query()->count());
    }

    #[Test]
    public function an_unknown_student_id_is_reported_and_never_creates_a_student(): void
    {
        $result = app(StudentCsvImporter::class)->import($this->csv("student_id,first_name,last_name\nEAC/2627/9999,Ghost,Student\n"));

        $this->assertSame(0, $result['created']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame(0, Student::query()->count());
    }

    #[Test]
    public function it_rejects_a_file_without_the_required_columns(): void
    {
        $this->expectException(ValidationException::class);
        app(StudentCsvImporter::class)->import($this->csv("name,class\nAda,JSS1\n"));
    }
}
