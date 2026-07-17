<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\App;
use Helpers\OutboundUrl;
use PHPUnit\Framework\TestCase;

$appHelper = dirname(__DIR__, 2).'/app/helpers/App.php';

if (is_file($appHelper)) {
    require_once $appHelper;
}

final class IframePolicySsrfTest extends TestCase
{
    public function testIframePolicyRejectsLoopbackWithoutConnecting(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl is required for the loopback SSRF regression test.');
        }

        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        self::assertIsResource($server, $errorMessage);

        $address = stream_socket_get_name($server, false);
        self::assertIsString($address);
        $port = (int) substr(strrchr($address, ':'), 1);
        $connectionMarker = tempnam(sys_get_temp_dir(), 'iframe-ssrf-');
        self::assertIsString($connectionMarker);
        unlink($connectionMarker);

        $pid = pcntl_fork();
        self::assertGreaterThanOrEqual(0, $pid);

        if ($pid === 0) {
            $connection = stream_socket_accept($server, 2);

            if (is_resource($connection)) {
                file_put_contents($connectionMarker, 'connected');
                $response = "HTTP/1.1 200 OK\r\n"
                    ."X-Frame-Options: DENY\r\n"
                    ."Content-Length: 0\r\n"
                    ."Connection: close\r\n\r\n";
                fwrite($connection, $response);
                fclose($connection);
            }

            fclose($server);
            exit(0);
        }

        try {
            self::assertFalse(App::iframePolicy('http://127.0.0.1:'.$port.'/probe'));
            pcntl_waitpid($pid, $status);
            self::assertFalse(is_file($connectionMarker), 'The iframe probe connected to a loopback service.');
        } finally {
            fclose($server);

            if (is_file($connectionMarker)) {
                unlink($connectionMarker);
            }

            pcntl_waitpid($pid, $status, WNOHANG);
        }
    }

    public function testIframePolicyDoesNotUseTheUnguardedHeaderWrapper(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/App.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('get_headers(', $source);
    }

    public function testIframePolicyUsesAPinnedBoundedNonRedirectingHeadProbe(): void
    {
        $resolverCalls = 0;
        $transportCalls = 0;

        $blocked = App::iframePolicy(
            'https://frame-policy.example/probe',
            static function (string $host) use (&$resolverCalls): array {
                $resolverCalls++;
                self::assertSame('frame-policy.example', $host);
                return ['93.184.216.34'];
            },
            static function (string $url, array $options) use (&$transportCalls): bool {
                $transportCalls++;
                self::assertSame('https://frame-policy.example/probe', $url);
                self::assertFalse($options[CURLOPT_FOLLOWLOCATION]);
                self::assertSame(0, $options[CURLOPT_MAXREDIRS]);
                self::assertSame(2, $options[CURLOPT_CONNECTTIMEOUT]);
                self::assertSame(4, $options[CURLOPT_TIMEOUT]);
                self::assertSame(OutboundUrl::MAX_RESPONSE_BYTES, $options[CURLOPT_MAXFILESIZE]);
                self::assertSame(['frame-policy.example:443:93.184.216.34'], $options[CURLOPT_RESOLVE]);
                self::assertTrue($options[CURLOPT_NOBODY]);

                $header = $options[CURLOPT_HEADERFUNCTION];
                self::assertSame(17, $header(null, "HTTP/1.1 200 OK\r\n"));
                self::assertSame(23, $header(null, "X-Frame-Options: DENY\r\n"));
                self::assertSame(2, $header(null, "\r\n"));

                return true;
            }
        );

        self::assertTrue($blocked);
        self::assertSame(1, $resolverCalls);
        self::assertSame(1, $transportCalls);
    }

    public function testIframePolicyPreservesContentSecurityPolicyDetection(): void
    {
        $blocked = App::iframePolicy(
            'https://frame-policy.example/probe',
            static fn (string $host): array => ['93.184.216.34'],
            static function (string $url, array $options): bool {
                $header = $options[CURLOPT_HEADERFUNCTION];
                $header(null, "Content-Security-Policy: default-src 'self'; frame-ancestors 'self'\r\n");
                return true;
            }
        );

        self::assertTrue($blocked);
    }

    public function testIframePolicyFailsClosedWhenResponseHeadersExceedTheLimit(): void
    {
        $blocked = App::iframePolicy(
            'https://frame-policy.example/probe',
            static fn (string $host): array => ['93.184.216.34'],
            static function (string $url, array $options): bool {
                $header = $options[CURLOPT_HEADERFUNCTION];
                self::assertSame(0, $header(null, 'X-Oversized: '.str_repeat('x', 65536)."\r\n"));
                return false;
            }
        );

        self::assertTrue($blocked);
    }

    public function testIframePolicyAllowsDestinationsWithoutBlockingHeaders(): void
    {
        $blocked = App::iframePolicy(
            'https://frame-policy.example/probe',
            static fn (string $host): array => ['93.184.216.34'],
            static function (string $url, array $options): bool {
                $header = $options[CURLOPT_HEADERFUNCTION];
                $header(null, "Content-Type: text/html\r\n");
                return true;
            }
        );

        self::assertFalse($blocked);
    }
}
