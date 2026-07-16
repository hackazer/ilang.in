<?php

declare(strict_types=1);

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

require_once dirname(__DIR__, 2).'/app/traits/Links.php';
require_once dirname(__DIR__, 2).'/app/controllers/CronController.php';

final class CronDatabaseHotPathTest extends TestCase
{
    public function testSafetyScanUsesDeterministicBoundedWindowsAndWrapsSparseIds(): void
    {
        $state = new CronUrlQueryState(
            9_000,
            [(object) ['id' => 7_500], (object) ['id' => 8_900]],
            [(object) ['id' => 3], (object) ['id' => 400], (object) ['id' => 2_000]]
        );

        $method = new ReflectionMethod(\Cron::class, 'safetyScanUrls');
        $urls = $method->invoke(
            null,
            static fn(): CronUrlRecordingQuery => new CronUrlRecordingQuery($state),
            17,
            4
        );

        self::assertSame([7_500, 8_900, 3, 400], array_map(static fn(object $url): int => $url->id, $urls));
        self::assertSame(3, $state->queries, 'One MAX query plus two bounded window queries are expected.');
        self::assertSame([4, 2], $state->limits);
        self::assertSame(['gte', 'lt'], $state->windowTypes);
        self::assertSame(['id', 'id'], $state->orderColumns);
    }

    public function testSafetyScanDoesNotQueryWrapWindowWhenFirstWindowIsFull(): void
    {
        $tail = array_map(static fn(int $id): object => (object) ['id' => $id], range(501, 1_000));
        $state = new CronUrlQueryState(1_000, $tail, [(object) ['id' => 1]]);

        $method = new ReflectionMethod(\Cron::class, 'safetyScanUrls');
        $urls = $method->invoke(
            null,
            static fn(): CronUrlRecordingQuery => new CronUrlRecordingQuery($state),
            23,
            500
        );

        self::assertCount(500, $urls);
        self::assertSame(2, $state->queries, 'A full first window must avoid the wrap query.');
        self::assertSame(['gte'], $state->windowTypes);
        self::assertSame([500], $state->limits);
    }

    public function testSafetyScanPivotIsStableWithinBucketAndRotatesAcrossBuckets(): void
    {
        $method = new ReflectionMethod(\Cron::class, 'safetyScanStartId');

        $first = $method->invoke(null, 10_000, 1_000);
        $repeat = $method->invoke(null, 10_000, 1_000);
        $next = $method->invoke(null, 10_000, 1_001);

        self::assertSame($first, $repeat);
        self::assertGreaterThanOrEqual(1, $first);
        self::assertLessThanOrEqual(10_000, $first);
        self::assertNotSame($first, $next);
    }

    public function testCronQueriesRemainSargableAndDoNotUseRandomSorts(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/controllers/CronController.php');

        self::assertStringNotContainsString('RAND()', $source);
        self::assertStringNotContainsString('DATE(date)', $source);
        self::assertStringContainsString("whereLt('date', \$cutoff)", $source);
        self::assertStringContainsString("whereGte('id', \$startId)", $source);
        self::assertStringContainsString("whereLt('id', \$startId)", $source);
        self::assertStringContainsString("orderByAsc('id')", $source);
        self::assertStringContainsString('Data for users {$ids} were removed.', $source);
        self::assertStringContainsString('{$i} urls were blocked.', $source);
    }
}

final class CronUrlQueryState
{
    public int $queries = 0;

    /** @var list<int> */
    public array $limits = [];

    /** @var list<string> */
    public array $windowTypes = [];

    /** @var list<string> */
    public array $orderColumns = [];

    /**
     * @param list<object> $tail
     * @param list<object> $head
     */
    public function __construct(
        public int $maxId,
        public array $tail,
        public array $head
    ) {
    }
}

final class CronUrlRecordingQuery
{
    private ?string $windowType = null;
    private int $limit = 0;

    public function __construct(private CronUrlQueryState $state)
    {
        $this->state->queries++;
    }

    public function max(string $column): int
    {
        TestCase::assertSame('id', $column);

        return $this->state->maxId;
    }

    public function whereGte(string $column, int $value): self
    {
        TestCase::assertSame('id', $column);
        TestCase::assertGreaterThanOrEqual(1, $value);
        $this->windowType = 'gte';

        return $this;
    }

    public function whereLt(string $column, int $value): self
    {
        TestCase::assertSame('id', $column);
        TestCase::assertGreaterThan(1, $value);
        $this->windowType = 'lt';

        return $this;
    }

    public function orderByAsc(string $column): self
    {
        $this->state->orderColumns[] = $column;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        $this->state->limits[] = $limit;

        return $this;
    }

    /** @return list<object> */
    public function findMany(): array
    {
        TestCase::assertNotNull($this->windowType);
        $this->state->windowTypes[] = $this->windowType;
        $rows = $this->windowType === 'gte' ? $this->state->tail : $this->state->head;

        return array_slice($rows, 0, $this->limit);
    }
}
