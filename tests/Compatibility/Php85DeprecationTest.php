<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class Php85DeprecationTest extends TestCase
{
    public function testTrackedPhpSourcesDoNotCallDeprecatedCurlClose(): void
    {
        $root = dirname(__DIR__, 2);
        $files = [
            'storage/app/wpplugin.php',
            'storage/themes/default/integrations/wordpress.php',
            'storage/themes/default/pages/api.php',
            'storage/themes/ilangin-child/integrations/wordpress.php',
            'storage/themes/ilangin-child/pages/api.php',
        ];

        foreach ($files as $file) {
            $source = file_get_contents($root.'/'.$file);
            self::assertNotFalse($source, $file);
            self::assertDoesNotMatchRegularExpression('/\\bcurl_close\\s*\\(/', (string) $source, $file);
        }
    }

    public function testPhpLintScansAllTrackedPhpSourcesForDeprecatedCurlClose(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/scripts/lint-php.sh');
        self::assertNotFalse($script);
        self::assertStringContainsString("git ls-files '*.php'", (string) $script);
        self::assertStringContainsString('curl_close', (string) $script);
    }
}
