<?php

namespace Tests\Feature;

use App\Filament\Pages\StudentPinSlips;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** PIN slips can be printed in bulk for a class or for the whole school. */
class BulkPinSlipsTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $jss1;
    private SchoolClass $jss2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        $this->jss1 = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $this->jss2 = SchoolClass::query()->create(['name' => 'JSS2', 'is_active' => true]);
        foreach ([['Ada', $this->jss1], ['Bola', $this->jss1], ['Chi', $this->jss2]] as [$name, $class]) {
            Student::query()->create(['first_name' => $name, 'last_name' => 'Student', 'school_class_id' => $class->id, 'status' => 'active']);
        }
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);
    }

    #[Test]
    public function a_whole_class_can_be_printed_and_only_that_class_gets_pins(): void
    {
        Livewire::test(StudentPinSlips::class)
            ->fillForm(['scope' => 'class', 'which' => 'without_pin', 'school_class_id' => $this->jss1->id])
            ->call('save')
            ->assertFileDownloaded();

        $this->assertSame(2, Student::query()->where('school_class_id', $this->jss1->id)->whereNotNull('pin_hash')->count());
        $this->assertSame(0, Student::query()->where('school_class_id', $this->jss2->id)->whereNotNull('pin_hash')->count());
    }

    #[Test]
    public function the_whole_school_downloads_as_one_zip_with_a_pdf_per_class(): void
    {
        Livewire::test(StudentPinSlips::class)
            ->fillForm(['scope' => 'school', 'which' => 'without_pin'])
            ->call('save')
            ->assertFileDownloaded();

        $this->assertSame(3, Student::query()->whereNotNull('pin_hash')->count());
    }

    #[Test]
    public function students_who_already_have_a_pin_keep_it_unless_everyone_is_chosen(): void
    {
        Livewire::test(StudentPinSlips::class)->fillForm(['scope' => 'class', 'which' => 'without_pin', 'school_class_id' => $this->jss1->id])->call('save');
        $before = Student::query()->where('first_name', 'Ada')->value('pin_hash');

        Livewire::test(StudentPinSlips::class)->fillForm(['scope' => 'class', 'which' => 'without_pin', 'school_class_id' => $this->jss1->id])->call('save')->assertNotified();
        $this->assertSame($before, Student::query()->where('first_name', 'Ada')->value('pin_hash'));

        Livewire::test(StudentPinSlips::class)->fillForm(['scope' => 'class', 'which' => 'all', 'school_class_id' => $this->jss1->id])->call('save')->assertFileDownloaded();
        $this->assertNotSame($before, Student::query()->where('first_name', 'Ada')->value('pin_hash'));
    }
}
