<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class TargetedLinkBlacklistTest extends TestCase
{
    public function testResolvedTargetUsesExactUrlAndDomainBlacklistChecks(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/controllers/LinkController.php');

        self::assertGreaterThanOrEqual(2, substr_count($source, '$this->domainBlacklisted($url->url)'));
        self::assertStringNotContainsString("bannedlink LIKE ?", $source);
        self::assertStringNotContainsString("whereRaw('bannedlink LIKE ?'", $source);
    }
}
