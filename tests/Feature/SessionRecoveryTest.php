<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/__test/session-expired', static function (): never {
            throw new TokenMismatchException('CSRF token mismatch.');
        });
    }

    #[Test]
    public function a_stale_login_form_redirects_back_instead_of_showing_page_expired(): void
    {
        $this->from('/staff/login')
            ->post('/__test/session-expired')
            ->assertRedirect('/staff/login')
            ->assertSessionHasErrors(['email' => 'Your session expired. Please try again.']);
    }
}
