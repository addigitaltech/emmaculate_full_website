<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelSessionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function an_authenticated_parent_is_sent_to_admin_login_instead_of_receiving_a_403(): void
    {
        $parent = User::factory()->create(['password' => Hash::make('Parent-Passw0rd!x')]);
        $parent->assignRole('Parent');

        $this->actingAs($parent)
            ->get('/admin')
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'This account is not authorized for the administrator panel. Please use the correct portal.']);

        $this->assertGuest();
    }
}
