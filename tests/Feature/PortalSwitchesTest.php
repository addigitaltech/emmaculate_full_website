<?php

namespace Tests\Feature;

use App\Models\AdmissionsSettings;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalSwitchesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    private function settings(array $values): void
    {
        SchoolSettings::query()->orderBy('id')->first()->forceFill($values)->save();
    }

    private function allOff(): void
    {
        $this->settings(['student_portal_enabled' => false, 'parent_portal_enabled' => false, 'staff_portal_enabled' => false, 'admission_portal_enabled' => false, 'public_result_check_enabled' => false]);
    }

    #[Test]
    public function switched_off_portals_disappear_from_the_whole_website(): void
    {
        $this->allOff();

        $this->get('/')->assertOk()
            ->assertDontSee('Student Portal')->assertDontSee('Staff Portal')->assertDontSee('Parent Portal')
            ->assertDontSee('Admission Portal')->assertDontSee('Results Portal');
    }

    #[Test]
    public function each_switch_adds_back_only_its_own_portal(): void
    {
        $this->allOff();
        $this->settings(['public_result_check_enabled' => true]);
        $this->get('/')->assertOk()->assertSee('Results Portal')->assertSee('/check-result', false)->assertDontSee('Student Portal')->assertDontSee('Parent Portal');

        $this->settings(['student_portal_enabled' => true]);
        $this->get('/')->assertOk()->assertSee('Student Portal')->assertSee('/student/login', false)->assertDontSee('Parent Portal');
    }

    #[Test]
    public function every_portal_has_its_own_sign_in_page(): void
    {
        $this->get('/student/login')->assertOk()->assertSee('Student ID')->assertDontSee('Forgot your password');
        $this->get('/parent/login')->assertOk()->assertSee('Forgot your password')->assertDontSee('Student ID');
        $this->get('/staff/login')->assertOk()->assertSee('Forgot your password')->assertDontSee('Student ID');
        $this->get('/login')->assertOk()->assertSee('Which portal do you need?')->assertDontSee('Student ID');
    }

    #[Test]
    public function a_switched_off_portal_shows_a_notice_instead_of_a_form(): void
    {
        $this->settings(['staff_portal_enabled' => false]);

        $this->get('/staff/login')->assertOk()->assertSee('switched off')->assertDontSee('Forgot your password');
    }

    #[Test]
    public function parents_and_staff_cannot_use_each_others_sign_in(): void
    {
        $parent = User::factory()->create(['password' => Hash::make('Parent-Passw0rd!x')]);
        $parent->assignRole('Parent');
        $teacher = User::factory()->create(['password' => Hash::make('Teacher-Passw0rd!x')]);
        $teacher->assignRole('Teacher');

        $this->post(route('login.staff.submit'), ['email' => $parent->email, 'password' => 'Parent-Passw0rd!x'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('login.parent.submit'), ['email' => $teacher->email, 'password' => 'Teacher-Passw0rd!x'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.parent.submit'), ['email' => $parent->email, 'password' => 'Parent-Passw0rd!x'])->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($parent);
    }

    #[Test]
    public function teachers_are_locked_out_when_the_staff_portal_is_off_but_administrators_are_not(): void
    {
        $teacher = User::factory()->create(['password' => Hash::make('Teacher-Passw0rd!x')]);
        $teacher->assignRole('Teacher');
        $admin = User::factory()->create();
        $admin->assignRole('Results Admin');
        $this->settings(['staff_portal_enabled' => false]);

        $this->post(route('login.submit'), ['email' => $teacher->email, 'password' => 'Teacher-Passw0rd!x'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($admin)->get(route('portal.dashboard'))->assertOk();
    }

    #[Test]
    public function applying_for_admission_is_hidden_when_the_admission_portal_is_off(): void
    {
        AdmissionsSettings::current()->forceFill(['status' => 'open'])->save();
        $this->get('/admissions')->assertOk()->assertSee('Submit application');

        $this->settings(['admission_portal_enabled' => false]);
        $this->get('/admissions')->assertOk()->assertDontSee('Submit application');
        $this->post(route('admissions.apply'), ['applicant_name' => 'X', 'applying_for' => 'JSS1', 'guardian_name' => 'Y', 'guardian_phone' => '0800'])->assertNotFound();
    }
}
