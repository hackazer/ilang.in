<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\ArchiveValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZipArchive;

require_once dirname(__DIR__, 2).'/app/helpers/ArchiveValidator.php';

final class ArchiveValidatorTest extends TestCase
{
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->paths) as $path) {
            $this->remove($path);
        }
    }

    public function testValidPluginAndThemePackagesExtract(): void
    {
        $plugin = $this->zip([
            'config.json' => '{"name":"Safe plugin"}',
            'plugin.php' => '<?php return true;',
            'assets/app.js' => 'console.log("ok");',
        ]);
        $theme = $this->zip([
            'config.json' => '{"name":"Safe theme"}',
            'layouts/main.php' => '<?php echo "theme";',
            'assets/app.css' => 'body { color: black; }',
        ]);
        $pluginDestination = $this->directory('plugin-package-');
        $themeDestination = $this->directory('theme-package-');

        (new ArchiveValidator())->extract($plugin, $pluginDestination, ArchiveValidator::TYPE_PLUGIN);
        (new ArchiveValidator())->extract($theme, $themeDestination, ArchiveValidator::TYPE_THEME);

        self::assertFileExists($pluginDestination.'/config.json');
        self::assertFileExists($pluginDestination.'/plugin.php');
        self::assertFileExists($themeDestination.'/config.json');
        self::assertFileExists($themeDestination.'/layouts/main.php');
    }

    public function testApplicationPackageIsValidatedWithoutAPluginManifest(): void
    {
        $archive = $this->zip([
            'app/controllers/UpdateController.php' => '<?php return true;',
            'public/static/app.js' => 'console.log("updated");',
        ]);
        $destination = $this->directory('application-package-');

        (new ArchiveValidator())->extract($archive, $destination, ArchiveValidator::TYPE_APPLICATION);

        self::assertFileExists($destination.'/app/controllers/UpdateController.php');
        self::assertFileExists($destination.'/public/static/app.js');
    }

    #[DataProvider('unsafePathProvider')]
    public function testUnsafePathsAreRejectedBeforeExtraction(string $entry): void
    {
        $archive = $this->zip([
            'config.json' => '{}',
            $entry => '<?php echo "unsafe";',
        ]);
        $destination = $this->directory('archive-destination-');

        $this->expectException(InvalidArgumentException::class);

        (new ArchiveValidator())->extract($archive, $destination, ArchiveValidator::TYPE_PLUGIN);
    }

    public static function unsafePathProvider(): array
    {
        return [
            'traversal' => ['../outside.php'],
            'absolute Unix path' => ['/tmp/outside.php'],
            'absolute Windows path' => ['C:\\outside.php'],
            'backslash traversal' => ['..\\outside.php'],
        ];
    }

    public function testSymlinkEntryIsRejected(): void
    {
        $archive = $this->zip(['config.json' => '{}']);
        $zip = new ZipArchive();
        self::assertTrue($zip->open($archive));
        self::assertTrue($zip->addFromString('linked.php', 'plugin.php'));
        self::assertTrue($zip->setExternalAttributesName('linked.php', ZipArchive::OPSYS_UNIX, 0120777 << 16));
        $zip->close();

        $this->expectException(InvalidArgumentException::class);

        (new ArchiveValidator())->extract(
            $archive,
            $this->directory('archive-symlink-'),
            ArchiveValidator::TYPE_PLUGIN
        );
    }

    public function testNestedArchiveIsRejected(): void
    {
        $archive = $this->zip([
            'config.json' => '{}',
            'payload.zip' => 'PK malicious nested archive',
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new ArchiveValidator())->validate($archive, ArchiveValidator::TYPE_PLUGIN);
    }

    public function testEntryCountLimitIsEnforced(): void
    {
        $archive = $this->zip([
            'config.json' => '{}',
            'plugin.php' => '<?php return true;',
            'extra.txt' => 'extra',
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new ArchiveValidator(maxEntries: 2))->validate($archive, ArchiveValidator::TYPE_PLUGIN);
    }

    public function testUncompressedByteLimitIsEnforced(): void
    {
        $archive = $this->zip([
            'config.json' => '{}',
            'large.txt' => str_repeat('x', 64),
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new ArchiveValidator(maxUncompressedBytes: 32))->validate($archive, ArchiveValidator::TYPE_PLUGIN);
    }

    #[DataProvider('unexpectedExecutableProvider')]
    public function testUnexpectedExecutableContentIsRejected(string $name, string $contents): void
    {
        $archive = $this->zip([
            'config.json' => '{}',
            $name => $contents,
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new ArchiveValidator())->validate($archive, ArchiveValidator::TYPE_THEME);
    }

    public static function unexpectedExecutableProvider(): array
    {
        return [
            'server script extension' => ['assets/install.sh', '#!/bin/sh\nid'],
            'PHP hidden in an asset' => ['assets/logo.jpg', '<?php echo "unsafe";'],
            'server configuration' => ['.htaccess', 'AddType application/x-httpd-php .jpg'],
        ];
    }

    public function testRootConfigIsRequired(): void
    {
        $archive = $this->zip(['wrapped/config.json' => '{}']);

        $this->expectException(InvalidArgumentException::class);

        (new ArchiveValidator())->validate($archive, ArchiveValidator::TYPE_PLUGIN);
    }

    public function testPluginAndThemeInstallersDelegateExtractionToValidator(): void
    {
        $root = dirname(__DIR__, 2);
        $plugins = file_get_contents($root.'/app/controllers/admin/PluginsController.php');
        $themes = file_get_contents($root.'/app/controllers/admin/ThemesController.php');

        self::assertIsString($plugins);
        self::assertIsString($themes);
        self::assertStringContainsString('ArchiveValidator', $plugins);
        self::assertStringContainsString('ArchiveValidator', $themes);
        self::assertStringNotContainsString('new \\ZipArchive', $plugins);
        self::assertStringNotContainsString('new \\ZipArchive', $themes);
        self::assertStringNotContainsString('->extractTo(', $plugins);
        self::assertStringNotContainsString('->extractTo(', $themes);
        self::assertGreaterThanOrEqual(2, substr_count($plugins, '->extract('));
        self::assertGreaterThanOrEqual(1, substr_count($themes, '->extract('));
    }

    private function zip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'package-archive-');
        self::assertNotFalse($path);
        $this->paths[] = $path;
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $name => $contents) {
            self::assertTrue($zip->addFromString($name, $contents));
        }

        $zip->close();

        return $path;
    }

    private function directory(string $prefix): string
    {
        $path = sys_get_temp_dir().'/'.$prefix.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($path, 0700));
        $this->paths[] = $path;

        return $path;
    }

    private function remove(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $this->remove($path.'/'.$entry);
        }

        rmdir($path);
    }
}
