<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class SourceAuditScriptTest extends TestCase
{
    public function testSourceAuditExcludesDependencyAndGeneratedVendorTrees(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/scripts/audit-source.sh');

        foreach (['!vendor/**', '!node_modules/**', '!public/static/vendor/**', '!public/static/frontend/libs/**'] as $glob) {
            self::assertGreaterThanOrEqual(2, substr_count($source, $glob), $glob);
        }
    }
}
