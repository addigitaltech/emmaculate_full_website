<?php

namespace Tests\Feature;

use App\Models\PortalLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** The public portal list now comes from the Super Admin switches (see PortalSwitchesTest). This keeps the old link validation covered. */
class PortalLinkStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function live_portal_link_rejects_a_missing_or_unsafe_destination(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        PortalLink::query()->create(['key' => 'bad-live', 'title' => 'Bad portal', 'url' => 'javascript:alert(1)', 'status' => 'live']);
    }
}
