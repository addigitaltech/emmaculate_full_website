<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCspTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_keep_the_strict_same_origin_script_policy(): void
    {
        $this->seed();

        $response = $this->get('/');
        $response->assertOk();

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self';", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
        $this->assertStringNotContainsString("'nonce-", $policy);
    }

    public function test_filament_uses_a_nonce_and_admin_scoped_eval_without_script_unsafe_inline(): void
    {
        $this->seed();

        $response = $this->get('/admin/login');
        $response->assertOk();

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $directives = collect(explode(';', $policy))->map(fn (string $directive): string => trim($directive));
        $scriptDirective = $directives->first(fn (string $directive): bool => str_starts_with($directive, 'script-src '));

        $this->assertNotNull($scriptDirective);
        $this->assertStringContainsString("'unsafe-eval'", $scriptDirective);
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptDirective);
        $this->assertStringContainsString('https://fonts.bunny.net', $policy);
        $hasNonce = preg_match("/'nonce-([A-Za-z0-9]+)'/", $scriptDirective, $nonceMatches);
        $this->assertSame(1, $hasNonce);
        $nonce = $nonceMatches[1];

        preg_match_all('/<script\\b([^>]*)>(.*?)<\\/script>/is', $response->getContent(), $scripts, PREG_SET_ORDER);
        $inlineScripts = array_values(array_filter(
            $scripts,
            fn (array $script): bool => ! preg_match('/\\bsrc\\s*=/i', $script[1]),
        ));

        $this->assertNotEmpty($inlineScripts, 'Filament login should contain inline initialization scripts.');
        foreach ($inlineScripts as $script) {
            $this->assertMatchesRegularExpression('/\\bnonce="'.preg_quote($nonce, '/').'"/', $script[1]);
        }
    }
}
