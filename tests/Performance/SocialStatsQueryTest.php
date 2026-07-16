<?php

declare(strict_types=1);

namespace Tests\Performance;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SocialStatsQueryTest extends TestCase
{
    #[DataProvider('statsControllerProvider')]
    public function testSocialReferrerCountsUseOneConditionalAggregate(string $relativePath): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/'.$relativePath);

        self::assertSame(1, substr_count($source, "'social_facebook'"), $relativePath);
        self::assertSame(1, substr_count($source, "'social_twitter'"), $relativePath);
        self::assertSame(1, substr_count($source, "'social_instagram'"), $relativePath);
        self::assertSame(1, substr_count($source, "'social_linkedin'"), $relativePath);
        self::assertStringContainsString('SUM(CASE WHEN', $source);

        self::assertStringNotContainsString(
            'whereRaw("(domain LIKE',
            $source,
            $relativePath.' must not issue one count query per social network.'
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function statsControllerProvider(): array
    {
        return [
            'browser stats' => ['app/controllers/StatsController.php'],
            'API stats' => ['app/controllers/api/LinksController.php'],
        ];
    }
}
