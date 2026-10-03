<?php

namespace Tests\Feature;

use App\Models\PortalLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalLinkStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function live_portals_link_to_the_sign_in_page_while_coming_soon_portals_cannot_be_opened(): void
    {
        PortalLink::query()->create(['key' => 'test-live', 'title' => 'Live student portal', 'description' => 'Access is open.', 'url' => '/login', 'status' => 'live', 'sort_order' => 90]);
        PortalLink::query()->create(['key' => 'test-soon', 'title' => 'Coming parent portal', 'description' => 'Access is being prepared.', 'url' => null, 'status' => 'coming_soon', 'sort_order' => 91]);
        PortalLink::query()->create(['key' => 'test-hidden', 'title' => 'Hidden portal', 'url' => '/login', 'status' => 'disabled', 'sort_order' => 92]);

        $this->get('/portal')
            ->assertOk()
            ->assertSee('Live student portal')
            ->assertSee('href="/login"', false)
            ->assertSee('Coming parent portal')
            ->assertSee('Coming Soon')
            ->assertDontSee('Hidden portal');
    }

    #[Test]
    public function live_portal_link_rejects_a_missing_or_unsafe_destination(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        PortalLink::query()->create(['key' => 'bad-live', 'title' => 'Bad portal', 'url' => 'javascript:alert(1)', 'status' => 'live']);
    }
}
