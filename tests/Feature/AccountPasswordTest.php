<?php

namespace Tests\Feature;

use App\Filament\Resources\ParentProfileResource\Pages\CreateParentProfile;
use App\Filament\Resources\TeacherResource\Pages\CreateTeacher;
use App\Models\ParentProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Teacher and parent profiles carry an email and a password; each person can change their own, and admin can too. */
class AccountPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Str0ng!Passw0rd#1';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    #[Test]
    public function creating_a_teacher_needs_an_email_and_a_password_and_sets_the_sign_in(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateTeacher::class)
            ->fillForm(['first_name' => 'Tola', 'last_name' => 'Bello', 'status' => 'active'])
            ->call('create')
            ->assertHasFormErrors(['email' => 'required', 'password' => 'required']);

        Livewire::test(CreateTeacher::class)
            ->fillForm(['first_name' => 'Tola', 'last_name' => 'Bello', 'email' => 'tola@example.test', 'password' => self::STRONG, 'status' => 'active'])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'tola@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('Teacher'));
        $this->assertTrue(Hash::check(self::STRONG, $user->password));
        $this->assertSame($user->id, Teacher::query()->where('email', 'tola@example.test')->value('user_id'));
    }

    #[Test]
    public function creating_a_parent_needs_an_email_and_a_password_and_sets_the_sign_in(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateParentProfile::class)
            ->fillForm(['full_name' => 'Mrs Grace Okoro', 'email' => 'grace@example.test', 'password' => self::STRONG])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'grace@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('Parent'));
        $this->assertTrue(Hash::check(self::STRONG, $user->password));
    }

    #[Test]
    public function a_weak_password_or_an_email_already_in_use_is_refused(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);
        $this->actingAs($this->admin);

        Livewire::test(CreateParentProfile::class)
            ->fillForm(['full_name' => 'Weak Password', 'email' => 'weak@example.test', 'password' => 'short'])
            ->call('create')
            ->assertHasFormErrors(['password']);

        Livewire::test(CreateParentProfile::class)
            ->fillForm(['full_name' => 'Taken Email', 'email' => 'taken@example.test', 'password' => self::STRONG])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    #[Test]
    public function an_administrator_can_set_a_new_password_when_editing_a_parent(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(CreateParentProfile::class)->fillForm(['full_name' => 'Mrs Grace Okoro', 'email' => 'grace@example.test', 'password' => self::STRONG])->call('create');
        $parent = ParentProfile::query()->firstOrFail();

        Livewire::test(\App\Filament\Resources\ParentProfileResource\Pages\EditParentProfile::class, ['record' => $parent->getKey()])
            ->fillForm(['password' => 'An0ther!Passw0rd#2'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('An0ther!Passw0rd#2', User::query()->where('email', 'grace@example.test')->value('password')));
    }

    #[Test]
    public function a_teacher_or_parent_can_change_their_own_password_from_the_portal(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Current!Passw0rd#1')]);
        $user->assignRole('Parent');

        $this->actingAs($user)->get(route('account.password'))->assertOk()->assertSee('Change password');

        $this->post(route('account.password.update'), ['current_password' => 'wrong-password', 'password' => 'Brand!New0Passw0rd', 'password_confirmation' => 'Brand!New0Passw0rd'])
            ->assertSessionHasErrors('current_password');

        $this->post(route('account.password.update'), ['current_password' => 'Current!Passw0rd#1', 'password' => 'Brand!New0Passw0rd', 'password_confirmation' => 'Brand!New0Passw0rd'])
            ->assertRedirect(route('portal.dashboard'));
        $this->assertTrue(Hash::check('Brand!New0Passw0rd', $user->fresh()->password));
    }

    #[Test]
    public function students_do_not_get_a_password_page(): void
    {
        $student = User::factory()->create();
        $student->assignRole('Student');

        $this->actingAs($student)->get(route('account.password'))->assertForbidden();
    }
}
