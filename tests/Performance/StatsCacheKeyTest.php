<?php

declare(strict_types=1);

namespace Tests\Performance;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use User\Stats;

require_once dirname(__DIR__, 2).'/app/controllers/user/StatsController.php';

final class StatsCacheKeyTest extends TestCase
{
    #[DataProvider('metricProvider')]
    public function testCacheKeysAreTenantScoped(string $metric): void
    {
        self::assertSame('stats.'.$metric.'.17', Stats::cacheKey($metric, 17));
        self::assertNotSame(Stats::cacheKey($metric, 17), Stats::cacheKey($metric, 18));
    }

    public function testCacheMissReadsAndWritesTheSameTenantKey(): void
    {
        $reads = [];
        $writes = [];

        $result = Stats::rememberStat(
            'chartlinks',
            42,
            static fn(): array => [['count' => 3]],
            3600,
            static function (string $key) use (&$reads): mixed {
                $reads[] = $key;

                return null;
            },
            static function (string $key, mixed $value, int $ttl) use (&$writes): void {
                $writes[] = [$key, $value, $ttl];
            }
        );

        self::assertSame([['count' => 3]], $result);
        self::assertSame(['stats.chartlinks.42'], $reads);
        self::assertSame([
            ['stats.chartlinks.42', [['count' => 3]], 3600],
        ], $writes);
    }

    public function testCacheHitDoesNotReloadOrRewriteTheValue(): void
    {
        $loadCount = 0;
        $writeCount = 0;

        $result = Stats::rememberStat(
            'chartclicks',
            42,
            static function () use (&$loadCount): array {
                $loadCount++;

                return [['count' => 9]];
            },
            3600,
            static fn(string $key): array => [['count' => 4]],
            static function () use (&$writeCount): void {
                $writeCount++;
            }
        );

        self::assertSame([['count' => 4]], $result);
        self::assertSame(0, $loadCount);
        self::assertSame(0, $writeCount);
    }

    public static function metricProvider(): array
    {
        return [
            'link chart' => ['chartlinks'],
            'click chart' => ['chartclicks'],
            'country map' => ['countrymaps'],
        ];
    }
}
