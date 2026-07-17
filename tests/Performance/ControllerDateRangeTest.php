<?php

declare(strict_types=1);

namespace Tests\Performance;

use Admin\Links as AdminLinks;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Stats;
use User\Dashboard as UserDashboard;

require_once dirname(__DIR__, 2).'/app/traits/Links.php';
require_once dirname(__DIR__, 2).'/app/controllers/StatsController.php';
require_once dirname(__DIR__, 2).'/app/controllers/user/DashboardController.php';
require_once dirname(__DIR__, 2).'/app/controllers/admin/LinksController.php';

final class ControllerDateRangeTest extends TestCase
{
    public function testStatsRangeTreatsSelectedDatesAsInclusiveCalendarDays(): void
    {
        [$start, $end] = $this->invokePrivateStatic(
            Stats::class,
            'reportDateRange',
            ['07/01/2026', '07/03/2026', new DateTimeImmutable('2026-07-17 00:00:00')]
        );

        self::assertSame('2026-07-01 00:00:00', $start->format('Y-m-d H:i:s'));
        self::assertSame('2026-07-04 00:00:00', $end->format('Y-m-d H:i:s'));
    }

    public function testStatsRangeRejectsInvalidAndReversedDates(): void
    {
        $today = new DateTimeImmutable('2026-07-17 00:00:00');

        foreach ([
            ['not-a-date', '07/03/2026'],
            ['07/04/2026', '07/03/2026'],
            ['02/30/2026', '03/01/2026'],
        ] as [$from, $to]) {
            [$start, $end] = $this->invokePrivateStatic(
                Stats::class,
                'reportDateRange',
                [$from, $to, $today]
            );

            self::assertSame('2026-07-03 00:00:00', $start->format('Y-m-d H:i:s'));
            self::assertSame('2026-07-18 00:00:00', $end->format('Y-m-d H:i:s'));
        }
    }

    public function testStatsPredicateBindsHalfOpenRangeParameters(): void
    {
        $query = new DatePredicateRecordingQuery();

        $result = $this->invokePrivateStatic(
            Stats::class,
            'applyDateRange',
            [
                $query,
                new DateTimeImmutable('2026-07-01 00:00:00'),
                new DateTimeImmutable('2026-07-04 00:00:00'),
            ]
        );

        self::assertSame($query, $result);
        self::assertSame([
            ['gte', 'date', '2026-07-01 00:00:00'],
            ['lt', 'date', '2026-07-04 00:00:00'],
        ], $query->dateCalls);
    }

    public function testUserLinkCutoffIsValidatedBoundAndSargable(): void
    {
        $query = new DatePredicateRecordingQuery();

        $result = $this->invokePrivateStatic(
            UserDashboard::class,
            'applyDateCutoff',
            [$query, '2026-07-17']
        );

        self::assertSame($query, $result);
        self::assertSame([
            ['lt', 'date', '2026-07-17 00:00:00'],
        ], $query->dateCalls);

        $invalidQuery = new DatePredicateRecordingQuery();
        $this->invokePrivateStatic(UserDashboard::class, 'applyDateCutoff', [$invalidQuery, '2026-02-30']);
        self::assertSame([], $invalidQuery->dateCalls);
    }

    public function testAdminLinkCutoffIsValidatedBoundAndSargable(): void
    {
        $query = new DatePredicateRecordingQuery();

        $result = $this->invokePrivateStatic(
            AdminLinks::class,
            'applyDateCutoff',
            [$query, '2026-07-17']
        );

        self::assertSame($query, $result);
        self::assertSame([
            ['lt', 'date', '2026-07-17 00:00:00'],
        ], $query->dateCalls);

        $invalidQuery = new DatePredicateRecordingQuery();
        $this->invokePrivateStatic(AdminLinks::class, 'applyDateCutoff', [$invalidQuery, 'yesterday']);
        self::assertSame([], $invalidQuery->dateCalls);
    }

    public function testOwnedControllersContainNoInterpolatedOrNonSargableDateFilters(): void
    {
        $root = dirname(__DIR__, 2);
        $stats = (string) file_get_contents($root.'/app/controllers/StatsController.php');
        $dashboard = (string) file_get_contents($root.'/app/controllers/user/DashboardController.php');
        $adminLinks = (string) file_get_contents($root.'/app/controllers/admin/LinksController.php');

        self::assertStringNotContainsString("BETWEEN '{\$from}", $stats);
        self::assertStringNotContainsString('23:59:59', $stats);
        self::assertSame(5, substr_count($stats, '[$start, $end] = self::reportDateRange('));

        self::assertStringNotContainsString('whereRaw("DATE(date)', $dashboard);
        self::assertStringNotContainsString("whereRaw('date >= \\\'", $dashboard);
        self::assertStringContainsString("whereGte('date', \$statsStart)", $dashboard);

        self::assertStringNotContainsString("whereRaw('DATE(date)", $adminLinks);
        self::assertSame(5, substr_count($adminLinks, 'self::applyDateCutoff($query,'));
    }

    private function invokePrivateStatic(string $class, string $method, array $arguments): mixed
    {
        if (!method_exists($class, $method)) {
            self::fail($class.'::'.$method.' is missing.');
        }

        $reflection = new ReflectionMethod($class, $method);

        return $reflection->invoke(null, ...$arguments);
    }
}

final class DatePredicateRecordingQuery
{
    /** @var list<array{string, string, string}> */
    public array $dateCalls = [];

    public function whereGte(string $column, string $value): self
    {
        $this->dateCalls[] = ['gte', $column, $value];

        return $this;
    }

    public function whereLt(string $column, string $value): self
    {
        $this->dateCalls[] = ['lt', $column, $value];

        return $this;
    }
}
