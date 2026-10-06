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
    public function it_creates_and_updates_students_and_reports_bad_rows(): void
    {
        $class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $arm = Arm::query()->create(['name' => 'R']);
        $class->arms()->attach($arm->id);

        $path = $this->csv("student_number,first_name,last_name,gender,date_of_birth,class,arm\n"
            ."A1,Ada,Okoro,female,2014-03-17,JSS1,R\n"
            ."A2,Bola,Ajayi,male,12/08/2013,JSS1,\n"
            ."A3,Chi,Eze,male,2013-01-01,SS9,\n"
            .",NoNumber,Row,male,,,\n");

        $result = app(StudentCsvImporter::class)->import($path);

        $this->assertSame(2, $result['created']);
        $this->assertCount(2, $result['errors']);
        $this->assertSame('Female', Student::query()->where('student_number', 'A1')->value('gender'));
        $this->assertSame($class->id, Student::query()->where('student_number', 'A2')->value('school_class_id'));

        $again = app(StudentCsvImporter::class)->import($this->csv("student_number,first_name,last_name\nA1,Adaeze,Okoro\n"));
        $this->assertSame(1, $again['updated']);
        $this->assertSame('Adaeze', Student::query()->where('student_number', 'A1')->value('first_name'));
        $this->assertSame(2, Student::query()->count());
    }

    #[Test]
    public function it_rejects_a_file_without_the_required_columns(): void
    {
        $this->expectException(ValidationException::class);
        app(StudentCsvImporter::class)->import($this->csv("name,class\nAda,JSS1\n"));
    }
}
