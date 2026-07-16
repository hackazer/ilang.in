<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CookieSecurityTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        putenv('TRUSTED_PROXY_CIDRS');
        putenv('TRUST_CLOUDFLARE');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        putenv('TRUSTED_PROXY_CIDRS');
        putenv('TRUST_CLOUDFLARE');
    }

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

    public function testSessionCookieDefaultsToSecureForDirectHttps(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '203.0.113.20',
            'HTTPS' => 'on',
        ];

        self::assertTrue(Request::cookieOptions(0)['secure']);
    }

    #[DataProvider('forwardedSecureSchemeProvider')]
    public function testSessionCookieUsesTrustedForwardedScheme(string $header, string $value): void
    {
        putenv('TRUSTED_PROXY_CIDRS=10.0.0.0/8');
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '10.0.0.9',
            $header => $value,
        ];

        self::assertTrue(Request::cookieOptions(0)['secure']);
    }

    #[DataProvider('forwardedSecureSchemeProvider')]
    public function testSessionCookieRejectsForwardedSchemeFromUntrustedPeer(string $header, string $value): void
    {
        putenv('TRUSTED_PROXY_CIDRS=10.0.0.0/8');
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '203.0.113.20',
            $header => $value,
        ];

        self::assertFalse(Request::cookieOptions(0)['secure']);
    }

    public static function forwardedSecureSchemeProvider(): array
    {
        return [
            'X-Forwarded-Proto' => ['HTTP_X_FORWARDED_PROTO', 'https'],
            'Forwarded' => ['HTTP_FORWARDED', 'for=198.51.100.25;proto=https'],
        ];
    }

    public function testSessionAppliesResolvedCookieOptionsBeforeStarting(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/core/Gem.class.php');
        self::assertNotFalse($source);

        $cookieOptionsPosition = strpos($source, 'session_set_cookie_params(Request::cookieOptions(0));');
        $sessionStartPosition = strpos($source, 'session_start();');

        self::assertNotFalse($cookieOptionsPosition);
        self::assertNotFalse($sessionStartPosition);
        self::assertLessThan($sessionStartPosition, $cookieOptionsPosition);
    }

    public function testBaselineSecurityHeadersAreDefined(): void
    {
        $headers = \Gem::securityHeaders();

        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
        self::assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        self::assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
    }
}
