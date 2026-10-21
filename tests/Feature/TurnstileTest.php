<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login as AdminLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Cloudflare Turnstile guards every sign-in form when its keys are set, and is invisible when they are not. */
class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    private function turnOn(): void
    {
        config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret-key']);
    }

    private function user(): User
    {
        $user = User::factory()->create(['email' => 'staff@example.test', 'password' => bcrypt('Str0ng!Passw0rd#1')]);
        $user->assignRole('Results Admin');

        return $user;
    }

    #[Test]
    public function nothing_changes_while_turnstile_is_off(): void
    {
        $this->user();

        $this->get(route('login.staff'))->assertOk()->assertDontSee('cf-turnstile');
        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1'])->assertRedirect();
        $this->assertAuthenticated();
    }

    #[Test]
    public function the_widget_is_shown_on_every_public_sign_in_form_when_turnstile_is_on(): void
    {
        $this->turnOn();

        foreach (['login.staff', 'login.parent', 'login.student', 'password.request'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('cf-turnstile', false)->assertSee('challenges.cloudflare.com', false);
        }
    }

    #[Test]
    public function a_sign_in_without_the_security_check_is_refused(): void
    {
        $this->turnOn();
        $this->user();
        Http::fake();

        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1'])->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
        Http::assertNothingSent();
    }

    #[Test]
    public function a_sign_in_with_a_token_cloudflare_rejects_is_refused(): void
    {
        $this->turnOn();
        $this->user();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false], 200)]);

        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1', 'cf-turnstile-response' => 'bad-token'])->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    #[Test]
    public function a_sign_in_with_a_token_cloudflare_accepts_goes_through(): void
    {
        $this->turnOn();
        $this->user();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'portal_login', 'hostname' => 'localhost'], 200)]);

        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1', 'cf-turnstile-response' => 'good-token'])->assertRedirect();
        $this->assertAuthenticated();
        Http::assertSent(fn ($request) => $request['secret'] === 'secret-key' && $request['response'] === 'good-token');
    }

    #[Test]
    public function if_cloudflare_cannot_be_reached_the_check_fails_safely(): void
    {
        $this->turnOn();
        $this->user();
        Http::fake(['challenges.cloudflare.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('down')]);

        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1', 'cf-turnstile-response' => 'token'])->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    #[Test]
    public function the_student_sign_in_the_result_checker_and_password_reset_also_need_the_check(): void
    {
        $this->turnOn();
        Http::fake();

        $this->post(route('student.login'), ['student_id' => 'EAC/2627/0001', 'surname' => 'OKORO'])->assertSessionHasErrors('cf-turnstile-response');
        $this->post(route('password.email'), ['email' => 'staff@example.test'])->assertSessionHasErrors('cf-turnstile-response');
    }

    #[Test]
    public function the_admin_sign_in_page_asks_for_the_check_and_refuses_without_it(): void
    {
        $this->turnOn();
        $this->user();

        Livewire::test(AdminLogin::class)
            ->fillForm(['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1'])
            ->call('authenticate')
            ->assertHasFormErrors(['turnstile']);
        $this->assertGuest();
    }

    #[Test]
    public function the_security_policy_allows_the_cloudflare_check_to_load(): void
    {
        $csp = (string) $this->get(route('login.staff'))->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('script-src \'self\' https://challenges.cloudflare.com', $csp);
        $this->assertStringContainsString('frame-src \'self\' https://www.openstreetmap.org https://challenges.cloudflare.com', $csp);
    }

    #[Test]
    public function a_token_earned_on_a_different_form_is_refused(): void
    {
        $this->turnOn();
        $this->user();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'check_result', 'hostname' => 'localhost'], 200)]);

        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1', 'cf-turnstile-response' => 'token'])->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    #[Test]
    public function a_token_earned_on_a_different_website_is_refused(): void
    {
        $this->turnOn();
        $this->user();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'portal_login', 'hostname' => 'somewhere-else.example'], 200)]);

        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1', 'cf-turnstile-response' => 'token'])->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    #[Test]
    public function the_allowed_hostnames_can_be_set_explicitly(): void
    {
        $this->turnOn();
        config(['services.turnstile.hostnames' => 'school.example.org, www.school.example.org']);
        $this->user();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'portal_login', 'hostname' => 'www.school.example.org'], 200)]);

        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1', 'cf-turnstile-response' => 'token'])->assertRedirect();
        $this->assertAuthenticated();
    }

    #[Test]
    public function the_widget_names_its_form_action(): void
    {
        $this->turnOn();

        $this->get(route('login.student'))->assertOk()->assertSee('data-action="student_login"', false);
        $this->get(route('password.request'))->assertOk()->assertSee('data-action="password_reset"', false);
    }

    #[Test]
    public function the_strict_check_can_be_relaxed_for_cloudflares_dummy_test_keys(): void
    {
        $this->turnOn();
        config(['services.turnstile.strict_context' => false]);
        $this->user();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'test', 'hostname' => 'example.com'], 200)]);

        $this->post(route('login.staff.submit'), ['email' => 'staff@example.test', 'password' => 'Str0ng!Passw0rd#1', 'cf-turnstile-response' => 'token'])->assertRedirect();
        $this->assertAuthenticated();
    }
}
