<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TrustedProxyIpTest extends TestCase
{
    private array $server;
    private array $request;
    private array $files;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->request = $_REQUEST;
        $this->files = $_FILES;

        putenv('TRUSTED_PROXY_CIDRS');
        putenv('TRUST_CLOUDFLARE');

        $_REQUEST = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_REQUEST = $this->request;
        $_FILES = $this->files;

        putenv('TRUSTED_PROXY_CIDRS');
        putenv('TRUST_CLOUDFLARE');
    }

    public function testForwardingHeadersAreIgnoredWithoutTrustedProxyConfiguration(): void
    {
        $request = $this->requestFrom([
            'REMOTE_ADDR' => '203.0.113.20',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.1',
            'HTTP_X_REAL_IP' => '198.51.100.2',
            'HTTP_CLIENT_IP' => '198.51.100.3',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.4',
            'HTTP_FORWARDED' => 'for=198.51.100.5',
        ]);

        self::assertSame('203.0.113.20', $request->ip());
    }

    public function testTrustedProxyChainReturnsTheNearestUntrustedAddress(): void
    {
        putenv('TRUSTED_PROXY_CIDRS=10.0.0.0/8, 2001:db8:100::/48');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '10.0.0.9',
            'HTTP_X_FORWARDED_FOR' => '192.0.2.99, 198.51.100.25, 10.1.2.3',
        ]);

        self::assertSame('198.51.100.25', $request->ip());
    }

    public function testUntrustedImmediatePeerCannotUseForwardedChain(): void
    {
        putenv('TRUSTED_PROXY_CIDRS=10.0.0.0/8');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '203.0.113.20',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.25, 10.1.2.3',
        ]);

        self::assertSame('203.0.113.20', $request->ip());
    }

    #[DataProvider('invalidForwardedChainProvider')]
    public function testInvalidForwardedChainFailsClosed(string $chain): void
    {
        putenv('TRUSTED_PROXY_CIDRS=10.0.0.0/8');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '10.0.0.9',
            'HTTP_X_FORWARDED_FOR' => $chain,
        ]);

        self::assertSame('10.0.0.9', $request->ip());
    }

    public static function invalidForwardedChainProvider(): array
    {
        return [
            'empty member' => ['198.51.100.25, , 10.1.2.3'],
            'invalid IPv4' => ['999.1.2.3, 10.1.2.3'],
            'address with port' => ['198.51.100.25:443, 10.1.2.3'],
            'header directive' => ['for=198.51.100.25, 10.1.2.3'],
        ];
    }

    public function testTrustedIpv6ProxyChainIsValidatedAndResolved(): void
    {
        putenv('TRUSTED_PROXY_CIDRS=2001:db8:100::/48');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '2001:db8:100::9',
            'HTTP_X_FORWARDED_FOR' => '2001:db8:ffff::25, 2001:db8:100::7',
        ]);

        self::assertSame('2001:db8:ffff::25', $request->ip());
    }

    public function testIpv4MappedIpv6AddressIsNormalized(): void
    {
        $request = $this->requestFrom([
            'REMOTE_ADDR' => '::ffff:192.0.2.25',
        ]);

        self::assertSame('192.0.2.25', $request->ip());
    }

    public function testCloudflareHeaderRequiresExplicitOptIn(): void
    {
        $request = $this->requestFrom([
            'REMOTE_ADDR' => '173.245.48.10',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.25',
        ]);

        self::assertSame('173.245.48.10', $request->ip());
    }

    public function testCloudflareHeaderIsAcceptedFromAnOfficialIpv4Edge(): void
    {
        putenv('TRUST_CLOUDFLARE=true');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '173.245.48.10',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.25',
        ]);

        self::assertSame('198.51.100.25', $request->ip());
    }

    public function testCloudflareHeaderIsAcceptedFromAnOfficialIpv6Edge(): void
    {
        putenv('TRUST_CLOUDFLARE=1');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '2606:4700::1234',
            'HTTP_CF_CONNECTING_IP' => '2001:db8:ffff::25',
        ]);

        self::assertSame('2001:db8:ffff::25', $request->ip());
    }

    public function testCloudflareHeaderFromNonCloudflarePeerIsRejected(): void
    {
        putenv('TRUST_CLOUDFLARE=true');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '203.0.113.20',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.25',
        ]);

        self::assertSame('203.0.113.20', $request->ip());
    }

    public function testInvalidCloudflareAddressFailsClosed(): void
    {
        putenv('TRUST_CLOUDFLARE=true');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '173.245.48.10',
            'HTTP_CF_CONNECTING_IP' => 'not-an-ip',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.25',
        ]);

        self::assertSame('173.245.48.10', $request->ip());
    }

    public function testDirectHttpsRemainsSecure(): void
    {
        $request = $this->requestFrom([
            'REMOTE_ADDR' => '203.0.113.20',
            'HTTPS' => 'on',
        ]);

        self::assertTrue($request->isSecure());
        self::assertSame('https', $request->http());
    }

    #[DataProvider('spoofedSecureSchemeHeaderProvider')]
    public function testUntrustedPeerCannotSpoofSecureScheme(string $header, string $value): void
    {
        putenv('TRUSTED_PROXY_CIDRS=10.0.0.0/8');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '203.0.113.20',
            $header => $value,
        ]);

        self::assertFalse($request->isSecure());
        self::assertSame('http', $request->http());
    }

    public static function spoofedSecureSchemeHeaderProvider(): array
    {
        return [
            'X-Forwarded-Proto' => ['HTTP_X_FORWARDED_PROTO', 'https'],
            'Forwarded' => ['HTTP_FORWARDED', 'for=198.51.100.25;proto=https'],
            'CF-Visitor' => ['HTTP_CF_VISITOR', '{"scheme":"https"}'],
        ];
    }

    #[DataProvider('trustedSecureSchemeHeaderProvider')]
    public function testTrustedProxyCanReportSecureScheme(string $header, string $value): void
    {
        putenv('TRUSTED_PROXY_CIDRS=10.0.0.0/8');

        $request = $this->requestFrom([
            'REMOTE_ADDR' => '10.0.0.9',
            $header => $value,
        ]);

        self::assertTrue($request->isSecure());
        self::assertSame('https', $request->http());
    }

    public static function trustedSecureSchemeHeaderProvider(): array
    {
        return [
            'X-Forwarded-Proto' => ['HTTP_X_FORWARDED_PROTO', 'https'],
            'Forwarded' => ['HTTP_FORWARDED', 'for=198.51.100.25;proto=https'],
        ];
    }

    private function requestFrom(array $server): Request
    {
        $_SERVER = array_merge([
            'REQUEST_METHOD' => 'GET',
        ], $server);

        return new Request();
    }
}
