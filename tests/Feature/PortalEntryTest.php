<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** There is one "which portal?" page: the sign-in chooser. The old separate Portals page is gone for visitors. */
class PortalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function visitors_who_open_the_portals_link_go_straight_to_the_sign_in_chooser(): void
    {
        $this->get(route('portal'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Which portal do you need?');
    }

    #[Test]
    public function the_mobile_menu_portals_button_links_to_the_sign_in_chooser(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('login').'"', false);
    }

    #[Test]
    public function a_signed_in_user_opening_the_portal_link_still_gets_their_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Parent');

        $this->actingAs($user)->get(route('portal'))->assertOk()->assertSee('Welcome');
    }
}
