<?php

namespace Tests\Feature;

use App\Filament\Pages\SchoolProfile;
use App\Models\CmsPage;
use App\Models\NewsPost;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchAndSchoolProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function the_public_search_finds_published_content_but_not_drafts(): void
    {
        CmsPage::query()->create([
            'title' => 'Our Learning Approach',
            'slug' => 'learning-approach',
            'excerpt' => 'How our school supports learners.',
            'content' => 'Learning approach information.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        NewsPost::query()->create(['title' => 'Science Fair Highlights', 'content' => 'Students explored science.', 'status' => 'published']);
        NewsPost::query()->create(['title' => 'Private Draft Science Fair', 'content' => 'Not public.', 'status' => 'draft']);

        $this->get('/search?q=science')
            ->assertOk()
            ->assertSee('Science Fair Highlights')
            ->assertDontSee('Private Draft Science Fair');

        $this->get('/search?q=learning')
            ->assertOk()
            ->assertSee('Our Learning Approach');
    }

    #[Test]
    public function the_visible_school_profile_can_edit_the_student_id_prefix(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        Livewire::actingAs($admin)
            ->test(SchoolProfile::class)
            ->assertFormFieldExists('student_id_prefix')
            ->fillForm(['student_id_prefix' => 'eacx'])
            ->call('save');

        $this->assertSame('EACX', SchoolSettings::current()->fresh()->student_id_prefix);
    }

    #[Test]
    public function portal_search_is_scoped_to_the_authenticated_student(): void
    {
        $account = User::factory()->create(['name' => 'Ada Portal']);
        $account->assignRole('Student');
        Student::query()->create(['user_id' => $account->id, 'student_number' => 'EAC-SEARCH-1', 'first_name' => 'Ada', 'last_name' => 'Visible', 'status' => 'active']);
        Student::query()->create(['student_number' => 'EAC-SEARCH-2', 'first_name' => 'Bola', 'last_name' => 'Private', 'status' => 'active']);

        $this->actingAs($account)->get('/portal/search?q=visible')
            ->assertOk()
            ->assertSee('Ada Visible')
            ->assertDontSee('Bola Private');

        $this->actingAs($account)->get('/portal/search?q=private')
            ->assertOk()
            ->assertDontSee('Bola Private');
    }
}
