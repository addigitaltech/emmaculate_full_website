<?php

namespace Tests\Unit\Website;

use App\Domain\Website\Support\SafePublicUrl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SafePublicUrlTest extends TestCase
{
    #[Test]
    public function it_allows_internal_and_https_links(): void
    {
        self::assertTrue(SafePublicUrl::allows('/about'));
        self::assertTrue(SafePublicUrl::allows('https://school.example/path'));
        self::assertTrue(SafePublicUrl::allows(null));
    }

    #[Test]
    public function it_rejects_unsafe_schemes_and_redirect_forms(): void
    {
        self::assertFalse(SafePublicUrl::allows('javascript:alert(1)'));
        self::assertFalse(SafePublicUrl::allows('//attacker.example'));
        self::assertFalse(SafePublicUrl::allows('http://school.example'));
        self::assertFalse(SafePublicUrl::allows('https://user:pass@school.example'));
    }
}
