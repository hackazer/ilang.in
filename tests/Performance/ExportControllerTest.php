<?php

declare(strict_types=1);

namespace Tests\Performance;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use User\Export as ExportController;

require_once dirname(__DIR__, 2).'/app/controllers/user/ExportController.php';

final class ExportControllerTest extends TestCase
{
    public function testDateRangeIncludesTheWholeSelectedEndDate(): void
    {
        self::assertSame(
            ['2026-07-01 00:00:00', '2026-07-18 00:00:00'],
            $this->invokePrivateStatic('parseDateRange', ['07/01/2026 - 07/17/2026'])
        );
    }

    public function testDateRangeIsAppliedAsSargableHalfOpenBounds(): void
    {
        $query = new ExportDateRangeRecordingQuery();
        $range = ['2026-07-01 00:00:00', '2026-07-18 00:00:00'];

        $result = $this->invokePrivateStatic('applyDateRange', [$query, $range]);

        self::assertSame($query, $result);
        self::assertSame([
            ['gte', 'date', '2026-07-01 00:00:00'],
            ['lt', 'date', '2026-07-18 00:00:00'],
        ], $query->conditions);
    }

    #[DataProvider('invalidDateRangeProvider')]
    public function testDateRangeRejectsMalformedImpossibleAndReversedValues(string $value): void
    {
        self::assertNull($this->invokePrivateStatic('parseDateRange', [$value]));
    }

    public static function invalidDateRangeProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'missing separator' => ['07/01/2026'];
        yield 'impossible date' => ['02/30/2026 - 03/05/2026'];
        yield 'reversed dates' => ['07/18/2026 - 07/17/2026'];
        yield 'unexpected format' => ['2026-07-01 - 2026-07-17'];
    }

    public function testCsvRowsEscapeDelimitersAndNeutralizeSpreadsheetFormulas(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        $this->invokePrivateStatic('writeCsvRow', [
            $stream,
            ['=1+1', ' +SUM(A1:A2)', '@command', "\tformula", 'safe,comma', 'quote"value', 42],
        ]);

        rewind($stream);
        $row = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);

        self::assertSame([
            "'=1+1",
            "' +SUM(A1:A2)",
            "'@command",
            "'\tformula",
            'safe,comma',
            'quote"value',
            '42',
        ], $row);
    }

    public function testRowsAreFetchedAndConsumedInBoundedBatches(): void
    {
        $rows = range(1, 5);
        $requests = [];
        $batchSizes = [];
        $consumed = [];

        $this->invokePrivateStatic('streamBatches', [
            static function (int $offset, int $limit) use ($rows, &$requests): array {
                $requests[] = [$offset, $limit];

                return array_slice($rows, $offset, $limit);
            },
            static function (array $batch) use (&$batchSizes, &$consumed): void {
                $batchSizes[] = count($batch);
                array_push($consumed, ...$batch);
            },
            2,
        ]);

        self::assertSame([[0, 2], [2, 2], [4, 2]], $requests);
        self::assertSame([2, 2, 1], $batchSizes);
        self::assertSame($rows, $consumed);
    }

    public function testCampaignNamesAreLoadedOnceForEachLinkBatch(): void
    {
        $queryCount = 0;
        $requestedIds = [];
        $links = [
            ['id' => 1, 'bundle' => 7],
            ['id' => 2, 'bundle' => 7],
            ['id' => 3, 'bundle' => 8],
            ['id' => 4, 'bundle' => null],
        ];

        $names = $this->invokePrivateStatic('campaignNamesForLinks', [
            $links,
            static function (array $ids) use (&$queryCount, &$requestedIds): array {
                $queryCount++;
                $requestedIds = $ids;

                return [
                    ['id' => 7, 'name' => 'Alpha'],
                    ['id' => 8, 'name' => 'Beta'],
                ];
            },
        ]);

        self::assertSame(1, $queryCount);
        self::assertSame([7, 8], $requestedIds);
        self::assertSame([7 => 'Alpha', 8 => 'Beta'], $names);
    }

    public function testLinkDetailsAreLoadedOncePerStatisticsBatchAndScopedToOwner(): void
    {
        $queryCount = 0;
        $requestedIds = [];
        $requestedOwner = null;
        $stats = [
            ['id' => 1, 'urlid' => 10],
            ['id' => 2, 'urlid' => 10],
            ['id' => 3, 'urlid' => 11],
        ];

        $links = $this->invokePrivateStatic('linksForStats', [
            $stats,
            42,
            static function (array $ids, int $ownerId) use (
                &$queryCount,
                &$requestedIds,
                &$requestedOwner
            ): array {
                $queryCount++;
                $requestedIds = $ids;
                $requestedOwner = $ownerId;

                return [
                    ['id' => 10, 'alias' => 'first', 'custom' => '', 'domain' => null],
                    ['id' => 11, 'alias' => 'second', 'custom' => '', 'domain' => 'sho.rt'],
                ];
            },
        ]);

        self::assertSame(1, $queryCount);
        self::assertSame([10, 11], $requestedIds);
        self::assertSame(42, $requestedOwner);
        self::assertSame('first', $links[10]['alias']);
        self::assertSame('sho.rt', $links[11]['domain']);
    }

    public function testLinkCsvRowPreservesTheExistingColumnOrder(): void
    {
        $row = $this->invokePrivateStatic('linkCsvRow', [[
            'domain' => 'sho.rt',
            'alias' => 'abc',
            'custom' => '-x',
            'url' => 'https://example.com/a,b',
            'bundle' => 7,
            'date' => '2026-07-17 12:34:56',
            'click' => 9,
            'uniqueclick' => 4,
        ], [7 => 'Launch'], 'default.test']);

        self::assertSame([
            'sho.rt/abc-x',
            'https://example.com/a,b',
            'Launch',
            '2026-07-17 12:34:56',
            9,
            4,
        ], $row);
    }

    public function testStatisticsCsvRowPreservesTheExistingColumnOrder(): void
    {
        $row = $this->invokePrivateStatic('statsCsvRow', [[
            'date' => '2026-07-17 12:34:56',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'browser' => 'Firefox',
            'os' => 'Linux',
            'language' => 'id',
            'domain' => 'ref.example',
            'referer' => 'https://ref.example/path',
        ], [
            'domain' => null,
            'alias' => 'abc',
            'custom' => '-x',
        ], 'sho.rt']);

        self::assertSame([
            'sho.rt/abc-x',
            '2026-07-17 12:34:56',
            'Jakarta',
            'Indonesia',
            'Firefox',
            'Linux',
            'id',
            'ref.example',
            'https://ref.example/path',
        ], $row);
    }

    public function testRangeBasedExportsApplyTheValidatedBoundsToStatisticsQueries(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/controllers/user/ExportController.php');

        foreach(['stats', 'campaign'] as $method){
            $methodSource = $this->methodSource($source, $method);

            self::assertStringContainsString('self::parseDateRange((string) $request->customreport)', $methodSource);
            self::assertStringContainsString('self::applyDateRange(', $methodSource);
        }
    }

    public function testEveryExportPathStreamsBoundedQueryBatches(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/controllers/user/ExportController.php');

        foreach(['links', 'single', 'stats', 'campaign'] as $method){
            $methodSource = $this->methodSource($source, $method);

            self::assertStringContainsString('self::downloadCsv(', $methodSource, $method);
            self::assertMatchesRegularExpression(
                '/->limit\(\$limit\)\s*->offset\(\$offset\)\s*->findArray\(\)/',
                $methodSource,
                $method
            );
            self::assertStringNotContainsString('$content', $methodSource, $method);
        }

        self::assertStringNotContainsString('File::contentDownload', $source);
        self::assertStringContainsString('self::streamBatches($loadBatch,', $source);
    }

    public function testExportPermissionsRemainTeamAwareAndOwnerScoped(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/controllers/user/ExportController.php');

        foreach(['links', 'single', 'stats', 'campaign'] as $method){
            self::assertStringContainsString(
                "teamPermission('export')",
                $this->methodSource($source, $method),
                $method
            );
        }

        $links = $this->methodSource($source, 'links');
        self::assertStringContainsString('$ownerId = Auth::user()->rID();', $links);
        self::assertStringContainsString("->where('userid', \$ownerId)", $links);

        $single = $this->methodSource($source, 'single');
        self::assertStringContainsString('$ownerId = Auth::user()->rID();', $single);
        self::assertStringContainsString("->where('userid', \$ownerId)", $single);

        $campaign = $this->methodSource($source, 'campaign');
        self::assertStringContainsString("Auth::user()->has('export')", $campaign);
        self::assertStringContainsString("->where('userid', \$ownerId)->first()", $campaign);
        self::assertStringContainsString("->where(\$urlTable.'.userid', \$ownerId)", $campaign);
    }

    private function invokePrivateStatic(string $method, array $arguments): mixed
    {
        if (!method_exists(ExportController::class, $method)) {
            self::fail(ExportController::class.'::'.$method.' is missing.');
        }

        $reflection = new ReflectionMethod(ExportController::class, $method);

        return $reflection->invoke(null, ...$arguments);
    }

    private function methodSource(string $source, string $method): string
    {
        $start = strpos($source, 'public function '.$method.'(');
        self::assertNotFalse($start, $method);
        $brace = strpos($source, '{', $start);
        self::assertNotFalse($brace, $method);
        $depth = 0;
        $length = strlen($source);

        for($offset = $brace; $offset < $length; $offset++){
            if($source[$offset] === '{'){
                $depth++;
            } elseif($source[$offset] === '}'){
                $depth--;
                if($depth === 0) return substr($source, $start, $offset - $start + 1);
            }
        }

        self::fail('Could not isolate '.$method.'.');
    }
}

final class ExportDateRangeRecordingQuery
{
    /** @var list<array{string, string, string}> */
    public array $conditions = [];

    public function whereGte(string $column, string $value): self
    {
        $this->conditions[] = ['gte', $column, $value];

        return $this;
    }

    public function whereLt(string $column, string $value): self
    {
        $this->conditions[] = ['lt', $column, $value];

        return $this;
    }
}
