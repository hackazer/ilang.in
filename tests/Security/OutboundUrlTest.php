<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Http;
use Helpers\OutboundUrl;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

$outboundUrl = dirname(__DIR__, 2).'/app/helpers/OutboundUrl.php';

if (is_file($outboundUrl)) {
    require_once $outboundUrl;
}

final class OutboundUrlTest extends TestCase
{
    #[DataProvider('unsafeUrlProvider')]
    public function testUnsafeDestinationsAreRejected(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        OutboundUrl::assertSafe($url);
    }

    public static function unsafeUrlProvider(): array
    {
        return [
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/file'],
            'gopher scheme' => ['gopher://127.0.0.1/'],
            'embedded username' => ['https://user@example.com/'],
            'embedded credentials' => ['https://user:secret@example.com/'],
            'trailing dot host' => ['https://example.com./'],
            'unicode host' => ['https://éxample.com/'],
            'loopback IPv4' => ['http://127.0.0.1/'],
            'private IPv4' => ['http://10.0.0.1/'],
            'carrier grade NAT' => ['http://100.64.0.1/'],
            'link local metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'documentation IPv4' => ['http://192.0.2.1/'],
            'multicast IPv4' => ['http://224.0.0.1/'],
            'reserved IPv4' => ['http://240.0.0.1/'],
            'loopback IPv6' => ['http://[::1]/'],
            'IPv4 mapped loopback' => ['http://[::ffff:127.0.0.1]/'],
            'NAT64 metadata' => ['http://[64:ff9b::a9fe:a9fe]/'],
            'dummy IPv6 prefix' => ['http://[100:0:0:1::1]/'],
            'unique local IPv6' => ['http://[fd00::1]/'],
            'link local IPv6' => ['http://[fe80::1]/'],
            'multicast IPv6' => ['http://[ff02::1]/'],
            'documentation IPv6' => ['http://[3fff::1]/'],
            'segment routing IPv6' => ['http://[5f00::1]/'],
            'unallocated IPv6' => ['http://[4000::1]/'],
        ];
    }

    public function testEveryDnsAnswerMustBePublic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OutboundUrl::assertSafe(
            'https://hooks.example.com/deliver',
            false,
            static fn (string $host): array => ['93.184.216.34', '127.0.0.1']
        );
    }

    public function testDnsFailureIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OutboundUrl::assertSafe(
            'https://missing.example/deliver',
            false,
            static fn (string $host): array => []
        );
    }

    public function testPublicProviderDestinationIsAcceptedAndPinned(): void
    {
        $target = OutboundUrl::assertSafe(
            'https://api.stripe.com/v1/payment_intents',
            false,
            static fn (string $host): array => ['54.187.216.72']
        );

        self::assertSame('api.stripe.com', $target['host']);
        self::assertSame(443, $target['port']);
        self::assertSame(['54.187.216.72'], $target['addresses']);
    }

    public function testPrivateNetworkAccessRequiresExplicitOptIn(): void
    {
        $target = OutboundUrl::assertSafe(
            'http://internal.service/health',
            true,
            static fn (string $host): array => ['10.20.30.40']
        );

        self::assertSame(['10.20.30.40'], $target['addresses']);

        $request = Http::url('http://internal.service/health');
        self::assertSame($request, $request->allowPrivateNetwork());
    }

    #[DataProvider('specialUseAddressProvider')]
    public function testPrivateOptInStillRejectsSpecialUseAddresses(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        OutboundUrl::assertSafe($url, true);
    }

    public static function specialUseAddressProvider(): array
    {
        return [
            'metadata endpoint' => ['http://169.254.169.254/latest/meta-data/'],
            'IPv4 multicast' => ['http://224.0.0.1/'],
            'IPv4 reserved' => ['http://240.0.0.1/'],
            'IPv6 multicast' => ['http://[ff02::1]/'],
            'NAT64 translation' => ['http://[64:ff9b::a9fe:a9fe]/'],
        ];
    }

    public function testCurlPolicyDisablesRedirectsAndEnforcesTlsAndBounds(): void
    {
        $target = [
            'scheme' => 'https',
            'host' => 'api.example.com',
            'port' => 443,
            'addresses' => ['93.184.216.34'],
        ];

        $options = OutboundUrl::curlOptions($target, 999, 999);

        self::assertFalse($options[CURLOPT_FOLLOWLOCATION]);
        self::assertSame(0, $options[CURLOPT_MAXREDIRS]);
        self::assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        self::assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        self::assertSame(OutboundUrl::MAX_CONNECT_TIMEOUT, $options[CURLOPT_CONNECTTIMEOUT]);
        self::assertSame(OutboundUrl::MAX_TOTAL_TIMEOUT, $options[CURLOPT_TIMEOUT]);
        self::assertSame(OutboundUrl::MAX_RESPONSE_BYTES, $options[CURLOPT_MAXFILESIZE]);
        self::assertSame(['api.example.com:443:93.184.216.34'], $options[CURLOPT_RESOLVE]);
        self::assertSame('', $options[CURLOPT_PROXY]);
        self::assertSame('*', $options[CURLOPT_NOPROXY]);
    }

    public function testLiteralIpDoesNotNeedADnsOverride(): void
    {
        $target = [
            'scheme' => 'https',
            'host' => '93.184.216.34',
            'port' => 443,
            'addresses' => ['93.184.216.34'],
        ];

        $options = OutboundUrl::curlOptions($target);

        self::assertArrayNotHasKey(CURLOPT_RESOLVE, $options);
    }

    public function testResponseWriterStopsAtTheConfiguredLimit(): void
    {
        $body = '';
        $limitExceeded = false;
        $writer = OutboundUrl::responseWriter($body, $limitExceeded, 5);

        self::assertSame(4, $writer(null, '1234'));
        self::assertSame(0, $writer(null, '56'));
        self::assertTrue($limitExceeded);
        self::assertSame('1234', $body);
    }

    public function testSharedHttpBoundaryRejectsLoopbackBeforeConnecting(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Http::url('http://127.0.0.1/')->get();
    }

    public function testExplicitPrivateOptInPreservesResponseBodies(): void
    {
        $body = $this->requestFromLocalServer('provider response');

        self::assertSame('provider response', $body);
    }

    public function testSharedHttpBoundaryAbortsOversizedResponses(): void
    {
        $response = $this->requestObjectFromLocalServer(str_repeat('x', OutboundUrl::MAX_RESPONSE_BYTES + 1));

        self::assertFalse($response->getBody());
        self::assertSame(
            'Outbound HTTP response exceeded the configured size limit.',
            $response->response('curl_error')
        );
    }

    private function requestFromLocalServer(string $responseBody): string|false
    {
        return $this->requestObjectFromLocalServer($responseBody)->getBody();
    }

    private function requestObjectFromLocalServer(string $responseBody): Http
    {
        if (!function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl is required for the local HTTP boundary test.');
        }

        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        self::assertIsResource($server, $errorMessage);

        $address = stream_socket_get_name($server, false);
        self::assertIsString($address);
        $port = (int) substr(strrchr($address, ':'), 1);
        $pid = pcntl_fork();

        self::assertGreaterThanOrEqual(0, $pid);

        if ($pid === 0) {
            $connection = stream_socket_accept($server, 5);

            if (is_resource($connection)) {
                while (($line = fgets($connection)) !== false && trim($line) !== '') {
                }

                $headers = "HTTP/1.1 200 OK\r\n"
                    .'Content-Length: '.strlen($responseBody)."\r\n"
                    ."Content-Type: text/plain\r\n"
                    ."Connection: close\r\n\r\n";
                $payload = $headers.$responseBody;

                while ($payload !== '') {
                    $written = fwrite($connection, $payload);

                    if ($written === false || $written === 0) {
                        break;
                    }

                    $payload = substr($payload, $written);
                }

                fclose($connection);
            }

            fclose($server);
            exit(0);
        }

        fclose($server);

        try {
            return Http::url('http://127.0.0.1:'.$port.'/provider')
                ->allowPrivateNetwork()
                ->get(['timeout' => 5]);
        } finally {
            pcntl_waitpid($pid, $status);
        }
    }
}
