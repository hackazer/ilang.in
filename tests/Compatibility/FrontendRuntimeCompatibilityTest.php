<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class FrontendRuntimeCompatibilityTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    public function testControllersLoadNativeIconPickerAndFontAwesomeSevenAssets(): void
    {
        $source = implode("\n", [
            file_get_contents(self::ROOT.'/app/controllers/admin/PlansController.php'),
            file_get_contents(self::ROOT.'/app/controllers/user/BioController.php'),
        ]);

        $this->assertStringNotContainsString('fontawesome-picker', $source);
        $this->assertStringNotContainsString('font-awesome/5.15.4', $source);
        $this->assertStringNotContainsString('.iconpicker(', $source);
        $this->assertStringContainsString("assets('frontend/libs/fontawesome-free/css/all.min.css')", $source);
        $this->assertStringContainsString("assets('icon-picker.min.css')", $source);
        $this->assertStringContainsString("assets('icon-picker.min.js')", $source);
        $this->assertStringContainsString('IconPicker.init(', $source);
    }

    public function testFirstPartySourcesDoNotCallDiscontinuedIconPickerOrNotificationPlugins(): void
    {
        $paths = array_merge(
            glob(self::ROOT.'/app/controllers/**/*.php') ?: [],
            [
                self::ROOT.'/public/static/bio.js',
                self::ROOT.'/public/static/server.js',
            ]
        );

        foreach ($paths as $path) {
            $source = (string) file_get_contents($path);
            $this->assertStringNotContainsString('.iconpicker(', $source, $path);
            $this->assertStringNotContainsString('fontawesome-picker', $source, $path);
            $this->assertStringNotContainsString('$.notify(', $source, $path);
        }
    }

    public function testNativeIconPickerAssetsExistAndAreSyntacticallyValid(): void
    {
        foreach (['icon-picker.js', 'icon-picker.min.js', 'icon-picker.css', 'icon-picker.min.css'] as $asset) {
            $this->assertFileExists(self::ROOT.'/public/static/'.$asset);
        }

        $command = sprintf(
            'node --check %s 2>&1',
            escapeshellarg(self::ROOT.'/public/static/icon-picker.js')
        );
        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
    }

    public function testLegacyWidgetRuntimesAreAbsentFromOwnedSources(): void
    {
        $paths = [];
        foreach ([self::ROOT.'/app', self::ROOT.'/core', self::ROOT.'/public/static'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if (!$file->isFile() || !preg_match('/\.(?:php|js)$/', $file->getFilename())) continue;
                if (str_contains($file->getPathname(), '/frontend/libs/')) continue;
                if (str_ends_with($file->getFilename(), '.min.js')) continue;
                $paths[] = $file->getPathname();
            }
        }

        $legacyPatterns = [
            '.spectrum(',
            '.datepicker(',
            '.daterangepicker(',
            'apply.daterangepicker',
            'moment(',
            '.fontselect(',
            '.iconpicker(',
            'jquery-mask-plugin',
            'fontawesome-picker',
        ];

        foreach ($paths as $path) {
            $source = (string) file_get_contents($path);
            foreach ($legacyPatterns as $pattern) {
                $this->assertStringNotContainsString($pattern, $source, $path);
            }
        }
    }

    public function testSharedWidgetAdaptersAndManagedAssetsAreConfigured(): void
    {
        $config = (string) file_get_contents(self::ROOT.'/app/config/cdn.php');

        foreach (['coloris', 'airdatepicker'] as $key) {
            $this->assertStringContainsString("'{$key}' => [", $config);
        }
        foreach (['spectrum', 'datetimepicker', 'daterangepicker'] as $key) {
            $this->assertStringNotContainsString("'{$key}' => [", $config);
        }

        foreach (['color-picker', 'date-picker', 'font-selector', 'input-mask'] as $adapter) {
            foreach (['js', 'min.js'] as $extension) {
                $path = self::ROOT.'/public/static/'.$adapter.'.'.$extension;
                $this->assertFileExists($path);
                exec('node --check '.escapeshellarg($path).' 2>&1', $output, $exitCode);
                $this->assertSame(0, $exitCode, $path."\n".implode("\n", $output));
            }
        }
    }
}
