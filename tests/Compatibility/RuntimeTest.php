<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class RuntimeTest extends TestCase
{
    public function testSupportedRuntimeIsPhp83OrNewer(): void
    {
        self::assertGreaterThanOrEqual(80300, PHP_VERSION_ID);
    }
}
