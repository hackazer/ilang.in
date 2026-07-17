<?php

declare(strict_types=1);

namespace Tests\Performance;

use Core\Request;
use PHPUnit\Framework\TestCase;

final class GeolocationCacheTest extends TestCase
{
    public function testApiGeolocationUsesBoundedFetchAndCachesNormalizedResult(): void
    {
        $writes = [];
        $request = new Request();

        $result = $request->country(
            '8.8.8.8',
            static function (string $url, int $timeout): object {
                self::assertSame('https://geo.example/8.8.8.8', $url);
                self::assertSame(2, $timeout);

                return (object) ['city' => 'Mountain View', 'country_name' => 'United States'];
            },
            static fn (string $key): null => null,
            static function (string $key, array $value, int $ttl) use (&$writes): void {
                $writes[] = [$key, $value, $ttl];
            },
            ['driver' => 'api', 'path' => 'https://geo.example/{IP}']
        );

        self::assertSame(['city' => 'Mountain View', 'state' => null, 'country' => 'United States'], $result);
        self::assertCount(1, $writes);
        self::assertSame(86400, $writes[0][2]);
        self::assertStringStartsWith('geoip.', $writes[0][0]);
        self::assertStringNotContainsString('8.8.8.8', $writes[0][0]);
    }

    public function testCachedGeolocationAvoidsNetworkFetch(): void
    {
        $request = new Request();
        $fetches = 0;

        $result = $request->country(
            '8.8.4.4',
            static function () use (&$fetches): object {
                $fetches++;
                return (object) [];
            },
            static fn (string $key): array => [
                'city' => 'Cached city',
                'state' => 'Cached state',
                'country' => 'Cached country',
            ],
            static fn (): null => null,
            ['driver' => 'api', 'path' => 'https://geo.example/{IP}']
        );

        self::assertSame(0, $fetches);
        self::assertSame('Cached country', $result['country']);
    }

    public function testApiFailureReturnsStableEmptyLocation(): void
    {
        $request = new Request();

        $result = $request->country(
            '1.1.1.1',
            static function (): never {
                throw new \RuntimeException('network unavailable');
            },
            static fn (): null => null,
            static fn (): null => null,
            ['driver' => 'api', 'path' => 'https://geo.example/{IP}']
        );

        self::assertSame(['city' => null, 'state' => null, 'country' => null], $result);
    }
}
