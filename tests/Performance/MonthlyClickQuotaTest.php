<?php

declare(strict_types=1);

namespace Tests\Performance;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Traits\Links;

require_once dirname(__DIR__, 2).'/app/traits/Links.php';

final class MonthlyClickQuotaHarness
{
    use Links;
}

final class MonthlyClickQuotaTest extends TestCase
{
    public function testQuotaCacheKeyIsScopedToUserAndCalendarMonth(): void
    {
        self::assertTrue(method_exists(MonthlyClickQuotaHarness::class, 'applyMonthlyClickQuota'));
        self::assertSame(
            'monthlyclicks.42.202607',
            MonthlyClickQuotaHarness::monthlyClickQuotaKey(42, new DateTimeImmutable('2026-07-31 23:59:59'))
        );
        self::assertSame(
            'monthlyclicks.42.202608',
            MonthlyClickQuotaHarness::monthlyClickQuotaKey(42, new DateTimeImmutable('2026-08-01 00:00:00'))
        );
    }

    public function testReachedQuotaIsCheckedBeforeAcceptedClickMutation(): void
    {
        self::assertTrue(method_exists(MonthlyClickQuotaHarness::class, 'applyMonthlyClickQuota'));

        $events = [];

        $accepted = MonthlyClickQuotaHarness::applyMonthlyClickQuota(
            42,
            100,
            static function () use (&$events): int {
                $events[] = 'database';

                return 0;
            },
            static function () use (&$events): void {
                $events[] = 'mutate-url-total';
            },
            static function (string $key) use (&$events): int {
                $events[] = 'cache-read:'.$key;

                return 100;
            },
            static function () use (&$events): void {
                $events[] = 'cache-write';
            },
            static function () use (&$events): void {
                $events[] = 'cache-invalidate';
            },
            new DateTimeImmutable('2026-07-16 12:00:00')
        );

        self::assertFalse($accepted);
        self::assertSame(['cache-read:monthlyclicks.42.202607'], $events);
    }

    public function testAcceptedClickInvalidatesCachedMonthlyCountAfterMutation(): void
    {
        self::assertTrue(method_exists(MonthlyClickQuotaHarness::class, 'applyMonthlyClickQuota'));

        $events = [];

        $accepted = MonthlyClickQuotaHarness::applyMonthlyClickQuota(
            42,
            100,
            static function () use (&$events): int {
                $events[] = 'database';

                return 0;
            },
            static function () use (&$events): void {
                $events[] = 'mutate-url-total';
            },
            static function (string $key) use (&$events): int {
                $events[] = 'cache-read:'.$key;

                return 99;
            },
            static function () use (&$events): void {
                $events[] = 'cache-write';
            },
            static function (string $key) use (&$events): void {
                $events[] = 'cache-invalidate:'.$key;
            },
            new DateTimeImmutable('2026-07-16 12:00:00')
        );

        self::assertTrue($accepted);
        self::assertSame([
            'cache-read:monthlyclicks.42.202607',
            'mutate-url-total',
            'cache-invalidate:monthlyclicks.42.202607',
        ], $events);
    }

    public function testUnavailableCacheStillLoadsDatabaseAndEnforcesQuota(): void
    {
        self::assertTrue(method_exists(MonthlyClickQuotaHarness::class, 'applyMonthlyClickQuota'));

        $events = [];

        $accepted = MonthlyClickQuotaHarness::applyMonthlyClickQuota(
            42,
            100,
            static function () use (&$events): int {
                $events[] = 'database';

                return 100;
            },
            static function () use (&$events): void {
                $events[] = 'mutate-url-total';
            },
            static function (string $key) use (&$events): null {
                $events[] = 'cache-read:'.$key;

                return null;
            },
            static function (string $key, int $count, int $ttl) use (&$events): void {
                $events[] = "cache-write:{$key}:{$count}:{$ttl}";
            },
            static function () use (&$events): void {
                $events[] = 'cache-invalidate';
            },
            new DateTimeImmutable('2026-07-16 12:00:00')
        );

        self::assertFalse($accepted);
        self::assertSame([
            'cache-read:monthlyclicks.42.202607',
            'database',
            'cache-write:monthlyclicks.42.202607:100:86400',
        ], $events);
    }

    public function testUnlimitedPlanMutatesWithoutReadingQuotaState(): void
    {
        self::assertTrue(method_exists(MonthlyClickQuotaHarness::class, 'applyMonthlyClickQuota'));

        $events = [];

        $accepted = MonthlyClickQuotaHarness::applyMonthlyClickQuota(
            42,
            0,
            static function () use (&$events): int {
                $events[] = 'database';

                return 0;
            },
            static function () use (&$events): void {
                $events[] = 'mutate-url-total';
            },
            static function () use (&$events): int {
                $events[] = 'cache-read';

                return 0;
            },
            static function () use (&$events): void {
                $events[] = 'cache-write';
            },
            static function () use (&$events): void {
                $events[] = 'cache-invalidate';
            }
        );

        self::assertTrue($accepted);
        self::assertSame(['mutate-url-total'], $events);
    }
}
