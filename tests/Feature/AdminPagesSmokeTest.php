<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Opens every admin list and create page as a Super Admin so a broken screen is caught before it reaches the school. */
class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    #[Test]
    public function every_resource_list_and_create_page_opens(): void
    {
        $failures = [];
        foreach (glob(app_path('Filament/Resources/*Resource.php')) as $file) {
            $class = 'App\\Filament\\Resources\\'.basename($file, '.php');
            if ($class === 'App\\Filament\\Resources\\AuthorizedResource' || ! class_exists($class)) {
                continue;
            }
            $pages = $class::getPages();
            foreach (['index', 'create'] as $page) {
                if (! isset($pages[$page]) || ($page === 'create' && ! $class::canCreate())) {
                    continue;
                }
                $status = $this->actingAs($this->admin)->get($class::getUrl($page))->getStatusCode();
                if ($status !== 200) {
                    $failures[] = class_basename($class).' '.$page.' => '.$status;
                }
            }
        }

        $this->assertSame([], $failures, 'These admin pages failed: '.implode(', ', $failures));
    }

    #[Test]
    public function the_simple_settings_pages_open(): void
    {
        foreach (['school-profile', 'portal-settings', 'assessment-grading', 'email-center'] as $slug) {
            $this->actingAs($this->admin)->get('/admin/'.$slug)->assertOk();
        }
    }

    #[Test]
    public function the_admin_login_links_back_to_the_website(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Back to the school website');
    }
}
