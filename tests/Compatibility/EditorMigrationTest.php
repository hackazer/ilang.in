<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class EditorMigrationTest extends TestCase
{
    private const JODIT_VERSION = '4.13.3';
    private const JODIT_JS_SHA256 = '7499f4ee79562deeeb0961be19d6ef37a0ed078d8c781d4b78b263c03b1bb088';
    private const JODIT_CSS_SHA256 = '4fa0f793769ac7951c092d5b79eda4e20e1839e9db386885860e45d7fe6d360b';

    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function formerInitializerProvider(): iterable
    {
        yield 'admin pages' => ['app/controllers/admin/PagesController.php', 2];
        yield 'admin faqs' => ['app/controllers/admin/FaqsController.php', 2];
        yield 'admin settings' => ['app/controllers/admin/SettingsController.php', 1];
        yield 'admin blog' => ['app/controllers/admin/BlogController.php', 2];
        yield 'admin dashboard and email templates' => ['app/controllers/admin/DashboardController.php', 6];
        yield 'user bio create and edit' => ['app/controllers/user/BioController.php', 2];
        yield 'default theme settings' => ['storage/themes/default/class/themeSettings.php', 1];
        yield 'child theme settings' => ['storage/themes/ilangin-child/class/themeSettings.php', 1];
    }

    #[DataProvider('formerInitializerProvider')]
    public function testFormerEditorInitializersUseTheSharedAdapter(string $relativePath, int $expectedCalls): void
    {
        $source = $this->read($relativePath);

        self::assertSame(
            $expectedCalls,
            substr_count($source, 'EditorAdapter.create('),
            $relativePath.' must migrate every former editor initializer.'
        );
    }

    public function testBioEditorUsesSharedLifecycleAndSynchronizesBeforeFormData(): void
    {
        foreach (['public/static/bio.js', 'public/static/bio.min.js'] as $relativePath) {
            $source = $this->read($relativePath);

            self::assertStringContainsString('EditorAdapter.value(', $source, $relativePath);
            self::assertStringContainsString('EditorAdapter.create(', $source, $relativePath);
            self::assertStringContainsString('EditorAdapter.destroy(', $source, $relativePath);
            self::assertStringContainsString('EditorAdapter.recreate(', $source, $relativePath);
            self::assertStringContainsString('EditorAdapter.syncForm(', $source, $relativePath);

            $syncPosition = strpos($source, 'EditorAdapter.syncForm(');
            $formDataPosition = strpos($source, 'new FormData(');

            self::assertNotFalse($syncPosition, $relativePath);
            self::assertNotFalse($formDataPosition, $relativePath);
            self::assertLessThan($formDataPosition, $syncPosition, $relativePath.' must synchronize before FormData is built.');
        }
    }

    public function testLegacyEditorGlobalAndCdnReferencesAreAbsentFromApplicationSources(): void
    {
        $legacyName = 'CK'.'EDITOR';
        $legacyPackage = 'ck'.'editor';
        $files = $this->sourceFiles(['app', 'public/static', 'storage/themes']);

        foreach ($files as $file) {
            $source = file_get_contents($file);
            self::assertNotFalse($source);
            self::assertStringNotContainsString($legacyName, $source, $file);
            self::assertStringNotContainsString($legacyPackage, strtolower($source), $file);
        }
    }

    public function testCdnConfigurationUsesOnlyPinnedSelfHostedJoditAssetsAndAdapter(): void
    {
        $source = $this->read('app/config/cdn.php');

        self::assertSame(2, substr_count($source, "'version' => '".self::JODIT_VERSION."'"));
        self::assertSame(2, substr_count($source, "assets('vendor/jodit/jodit.min.js')"));
        self::assertSame(2, substr_count($source, "assets('editor-adapter.js')"));
        self::assertSame(2, substr_count($source, "assets('vendor/jodit/jodit.min.css')"));
        self::assertStringNotContainsString('cdn.ckeditor.com', strtolower($source));
    }

    public function testVendoredJoditAssetsMatchOfficialNpmPackage(): void
    {
        $javascript = $this->root.'/public/static/vendor/jodit/jodit.min.js';
        $stylesheet = $this->root.'/public/static/vendor/jodit/jodit.min.css';
        $license = $this->root.'/public/static/vendor/jodit/LICENSE.txt';

        self::assertFileExists($javascript);
        self::assertFileExists($stylesheet);
        self::assertFileExists($license);
        self::assertSame(self::JODIT_JS_SHA256, hash_file('sha256', $javascript));
        self::assertSame(self::JODIT_CSS_SHA256, hash_file('sha256', $stylesheet));

        $javascriptSource = file_get_contents($javascript);
        $licenseSource = file_get_contents($license);
        self::assertNotFalse($javascriptSource);
        self::assertNotFalse($licenseSource);
        self::assertStringContainsString('Version: v'.self::JODIT_VERSION, $javascriptSource);
        self::assertStringContainsString('License(s): MIT', $javascriptSource);
        self::assertStringContainsString('Permission is hereby granted, free of charge', $licenseSource);
    }

    private function read(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertNotFalse($source, $relativePath);

        return $source;
    }

    /**
     * @param list<string> $directories
     * @return list<string>
     */
    private function sourceFiles(array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->root.'/'.$directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || !in_array($file->getExtension(), ['js', 'php'], true)) {
                    continue;
                }

                if (str_contains($file->getPathname(), '/public/static/vendor/jodit/')) {
                    continue;
                }

                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
