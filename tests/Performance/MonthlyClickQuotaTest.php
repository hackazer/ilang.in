<?php

declare(strict_types=1);

namespace Tests\Performance;

use DateTimeImmutable;
use Fiber;
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

                return 100;
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
            static function () use (&$events): void {
                $events[] = 'cache-invalidate';
            },
            new DateTimeImmutable('2026-07-16 12:00:00'),
            static function (string $key, callable $reservation) use (&$events): mixed {
                $events[] = 'lock:'.$key;

                return $reservation();
            }
        );

        self::assertFalse($accepted);
        self::assertSame([
            'cache-read:monthlyclicks.42.202607',
            'lock:monthlyclicks.42.202607',
            'database',
            'cache-write',
        ], $events);
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
            new DateTimeImmutable('2026-07-16 12:00:00'),
            static function (string $key, callable $reservation) use (&$events): mixed {
                $events[] = 'lock:'.$key;

                return $reservation();
            }
        );

        self::assertTrue($accepted);
        self::assertSame([
            'cache-read:monthlyclicks.42.202607',
            'lock:monthlyclicks.42.202607',
            'database',
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
            new DateTimeImmutable('2026-07-16 12:00:00'),
            static function (string $key, callable $reservation) use (&$events): mixed {
                $events[] = 'lock:'.$key;

                return $reservation();
            }
        );

        self::assertFalse($accepted);
        self::assertSame([
            'cache-read:monthlyclicks.42.202607',
            'lock:monthlyclicks.42.202607',
            'database',
            'cache-write:monthlyclicks.42.202607:100:86400',
        ], $events);
    }

    public function testTwoConcurrentRequestsAtNinetyNineReserveOnlyOneClick(): void
    {
        $count = 99;
        $mutations = 0;
        $locked = false;
        $results = [];

        $withReservation = static function (string $key, callable $reservation) use (&$locked): mixed {
            while ($locked) {
                Fiber::suspend('waiting-for-'.$key);
            }

            $locked = true;

            try {
                return $reservation();
            } finally {
                $locked = false;
            }
        };

        $request = static function (int $requestId) use (
            &$count,
            &$mutations,
            &$results,
            $withReservation
        ): void {
            $results[$requestId] = MonthlyClickQuotaHarness::applyMonthlyClickQuota(
                42,
                100,
                static function () use (&$count): int {
                    return $count;
                },
                static function () use (&$count, &$mutations): void {
                    Fiber::suspend('reservation-held');
                    $count++;
                    $mutations++;
                },
                static fn (): int => 99,
                static fn (): null => null,
                static fn (): null => null,
                new DateTimeImmutable('2026-07-16 12:00:00'),
                $withReservation
            );
        };

        $first = new Fiber(static fn () => $request(1));
        $second = new Fiber(static fn () => $request(2));

        self::assertSame('reservation-held', $first->start());
        self::assertSame('waiting-for-monthlyclicks.42.202607', $second->start());

        $first->resume();
        $second->resume();

        self::assertTrue($first->isTerminated());
        self::assertTrue($second->isTerminated());
        self::assertSame([1 => true, 2 => false], $results);
        self::assertSame(100, $count);
        self::assertSame(1, $mutations);
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
