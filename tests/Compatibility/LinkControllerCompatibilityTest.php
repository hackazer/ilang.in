<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class LinkControllerCompatibilityTest extends TestCase
{
    public function testNullableValuesAreNormalizedBeforeStringFunctions(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/controllers/LinkController.php');
        self::assertNotFalse($source);

        self::assertStringContainsString("serverString('http_accept_language')", $source);
        self::assertMatchesRegularExpression('/\$type\s*=\s*is_scalar\(\$url->type\s*\?\?\s*null\)/', $source);
        self::assertStringContainsString('preg_match("~overlay-(.*)~", $type)', $source);
    }
}
