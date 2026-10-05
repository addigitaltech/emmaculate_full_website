<?php

namespace Tests\Feature;

use App\Models\SchoolSettings;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DesignRefreshSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedLayoutDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function guest_auth_and_password_reset_views_render_with_school_layout_data(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('password.request'))->assertOk();
        $this->get(route('password.reset', ['token' => 'test-reset-token']))->assertOk();
    }

    #[Test]
    public function a_teacher_can_render_the_score_entry_view_with_school_layout_data(): void
    {
        $user = User::query()->create([
            'name' => 'Score Entry Teacher',
            'email' => 'score-entry-teacher@example.test',
            'password' => 'test-password',
        ]);
        $user->assignRole('Teacher');
        Teacher::query()->create([
            'user_id' => $user->id,
            'first_name' => 'Score Entry',
            'last_name' => 'Teacher',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('results.entry'))
            ->assertOk()
            ->assertSee('Enter assessment scores');
    }

    #[Test]
    public function design_refresh_fills_blank_branding_without_overwriting_admin_values(): void
    {
        $logoPath = storage_path('app/public/migrated-images/logo.jpg');
        $this->assertFileExists($logoPath);

        $settings = SchoolSettings::current();
        $settings->forceFill(['logo_path' => null, 'favicon_path' => null, 'description' => null])->save();
        (new DesignRefreshSeeder())->run();

        $settings->refresh();
        $this->assertSame('migrated-images/logo.jpg', $settings->logo_path);
        $this->assertSame('migrated-images/logo.jpg', $settings->favicon_path);
        $this->assertNotEmpty($settings->description);

        $settings->forceFill([
            'logo_path' => 'custom/logo.svg',
            'favicon_path' => 'custom/favicon.ico',
            'description' => 'Administrator-authored school description.',
        ])->save();
        (new DesignRefreshSeeder())->run();

        $settings->refresh();
        $this->assertSame('custom/logo.svg', $settings->logo_path);
        $this->assertSame('custom/favicon.ico', $settings->favicon_path);
        $this->assertSame('Administrator-authored school description.', $settings->description);
    }
}
