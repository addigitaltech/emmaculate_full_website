<?php

namespace Tests\Feature;

use App\Models\NavigationItem;
use App\Models\SchoolSettings;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function an_admin_edit_shows_up_immediately_even_though_pages_are_cached(): void
    {
        $this->get('/')->assertOk()->assertSee('Determined to make a difference');

        $settings = SchoolSettings::query()->orderBy('id')->first();
        $settings->forceFill(['motto' => 'A brand new motto'])->save();

        $this->get('/')->assertOk()->assertSee('A brand new motto');
    }

    #[Test]
    public function menu_changes_appear_after_saving(): void
    {
        $this->get('/')->assertOk();
        NavigationItem::query()->create(['menu' => 'main', 'label' => 'Brand New Menu', 'url' => '/faq', 'is_visible' => true, 'sort_order' => 99]);

        $this->get('/')->assertSee('Brand New Menu');
    }

    #[Test]
    public function the_cache_serves_the_stored_value_until_content_changes(): void
    {
        $calls = 0;
        $first = SiteCache::remember('probe', 60, function () use (&$calls) {
            $calls++;

            return 'one';
        });
        $second = SiteCache::remember('probe', 60, function () use (&$calls) {
            $calls++;

            return 'two';
        });
        $this->assertSame('one', $first);
        $this->assertSame('one', $second);
        $this->assertSame(1, $calls);

        SiteCache::flush();
        $this->assertSame('three', SiteCache::remember('probe', 60, fn () => 'three'));
    }
}
