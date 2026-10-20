<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** The admin menu is arranged in plain-language groups and the dashboard offers "Start here" buttons. */
class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function the_dashboard_shows_start_here_buttons_and_the_simple_menu_groups(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Start here')
            ->assertSee('Add a student')
            ->assertSee('Print PIN slips')
            ->assertSee('Students &amp; staff', false)
            ->assertSee('Fees &amp; payments', false)
            ->assertSee('Classes &amp; subjects', false)
            ->assertSee('Graduated students (archive)');
    }

    #[Test]
    public function the_buttons_follow_what_the_person_is_allowed_to_do(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Content Editor');

        $this->actingAs($editor)->get('/admin')
            ->assertOk()
            ->assertSee('Post news')
            ->assertDontSee('Add a student')
            ->assertDontSee('Portal switches');
    }
}
