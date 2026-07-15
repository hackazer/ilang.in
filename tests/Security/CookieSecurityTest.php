<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Request;
use PHPUnit\Framework\TestCase;

final class CookieSecurityTest extends TestCase
{
    public function testCookieDefaultsAreHttpOnlySameSiteAndPathScoped(): void
    {
        $options = Request::cookieOptions(123, false);

        self::assertSame(123, $options['expires']);
        self::assertSame('/', $options['path']);
        self::assertTrue($options['httponly']);
        self::assertSame('Lax', $options['samesite']);
        self::assertFalse($options['secure']);
    }

    public function testHttpsCookiesAreSecure(): void
    {
        self::assertTrue(Request::cookieOptions(123, true)['secure']);
    }
}
