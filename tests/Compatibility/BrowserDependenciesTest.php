<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class BrowserDependenciesTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    public function testBrowserDependencyManifestPinsSupportedStableVersions(): void
    {
        $package = json_decode((string) file_get_contents(self::ROOT.'/package.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('3.7.1', $package['dependencies']['jquery']);
        $this->assertSame('4.6.2', $package['dependencies']['bootstrap']);
        $this->assertSame('4.5.1', $package['dependencies']['chart.js']);
        $this->assertSame('4.1.0', $package['dependencies']['select2']);
        $this->assertSame('2.0.11', $package['dependencies']['clipboard']);
        $this->assertSame('4.29.2', $package['dependencies']['feather-icons']);
        $this->assertSame('1.7.0', $package['dependencies']['jsvectormap']);
        $this->assertSame('1.44.0', $package['dependencies']['ace-builds']);
        $this->assertSame('11.11.1', $package['dependencies']['@highlightjs/cdn-assets']);
        $this->assertSame('2.30.1', $package['dependencies']['moment']);
        $this->assertSame('3.1.0', $package['dependencies']['daterangepicker']);
        $this->assertSame('4.38.0', $package['dependencies']['@yaireo/tagify']);
        $this->assertSame('3.1.1', $package['dependencies']['cookieconsent']);
        $this->assertSame('1.14.16', $package['dependencies']['jquery-mask-plugin']);
        $this->assertSame('1.1.3', $package['dependencies']['svg-injector']);
        $this->assertSame('3.2.1', $package['dependencies']['blockadblock']);
        $this->assertSame('1.8.1', $package['dependencies']['spectrum-colorpicker']);
        $this->assertSame('3.2.0', $package['dependencies']['fontawesome-iconpicker']);
        $this->assertSame('1.1.0', $package['dependencies']['fontselect-jquery-plugin']);
        $this->assertSame('5.49.0', $package['devDependencies']['terser']);
        $this->assertStringContainsString('jQuery 3.7.1', $package['browserCompatibility']['holds']['jquery']);
        $this->assertStringContainsString('Bootstrap 4.6.2', $package['browserCompatibility']['holds']['publicBootstrap']);
        $this->assertStringContainsString('Popper 1', $package['browserCompatibility']['holds']['publicPopper']);
        $this->assertStringContainsString('Core-JS 2', $package['browserCompatibility']['holds']['dashboardBundle']);
        $this->assertStringContainsString('discontinued', $package['browserCompatibility']['holds']['fontawesomeIconpicker']);
        $this->assertStringContainsString('incompatible', $package['browserCompatibility']['holds']['fontselectPackageLayout']);
        $this->assertArrayNotHasKey('bootstrap-notify', $package['dependencies']);
        $this->assertArrayNotHasKey('bootstrap-tagsinput', $package['dependencies']);
    }

    public function testGeneratedAssetsArePinnedAndReproducible(): void
    {
        $manifest = json_decode((string) file_get_contents(self::ROOT.'/public/static/vendor-manifest.json'), true, 512, JSON_THROW_ON_ERROR);

        foreach ([
            'cookieconsent.min.css',
            'cookieconsent.min.js',
            'frontend/libs/jquery-mask-plugin/dist/jquery.mask.min.js',
            'frontend/libs/svg-injector/dist/svg-injector.min.js',
            'frontend/libs/blockadblock/blockadblock.min.js',
            'frontend/libs/spectrum/spectrum.min.css',
            'frontend/libs/spectrum/spectrum.min.js',
            'frontend/libs/fontawesome-picker/dist/css/fontawesome-iconpicker.min.css',
            'frontend/libs/fontawesome-picker/dist/js/fontawesome-iconpicker.min.js',
        ] as $path) {
            $this->assertArrayHasKey($path, $manifest['files']);
        }

        foreach ($manifest['files'] as $path => $metadata) {
            $absolute = self::ROOT.'/public/static/'.$path;
            $this->assertFileExists($absolute, $path);
            $this->assertSame($metadata['bytes'], filesize($absolute), $path);
            $this->assertSame($metadata['sha256'], hash_file('sha256', $absolute), $path);
        }
    }

    public function testLayoutsDoNotLoadDuplicateOrAbandonedFrameworks(): void
    {
        $layouts = glob(self::ROOT.'/storage/themes/{default,ilangin-child}/{layouts,admin/layouts}/*.php', GLOB_BRACE);
        $this->assertNotEmpty($layouts);

        foreach ($layouts as $layout) {
            $source = (string) file_get_contents($layout);
            $this->assertSame(0, substr_count($source, 'bootstrap-notify'), $layout);
            $this->assertLessThanOrEqual(1, substr_count($source, "jquery.min.js"), $layout);
            $this->assertLessThanOrEqual(1, substr_count($source, "bundle.pack.js"), $layout);
        }

        foreach (['default', 'ilangin-child'] as $theme) {
            $dashboard = (string) file_get_contents(self::ROOT."/storage/themes/{$theme}/layouts/dashboard.php");
            $admin = (string) file_get_contents(self::ROOT."/storage/themes/{$theme}/admin/layouts/main.php");
            $this->assertSame(1, substr_count($dashboard, 'backend/vendor.min.js'));
            $this->assertSame(1, substr_count($admin, 'backend/admin-vendor.min.js'));
            $this->assertSame(1, substr_count($dashboard, "assets('custom.min.js')"));
            $this->assertSame(1, substr_count($admin, "assets('custom.min.js')"));
            $this->assertStringNotContainsString("assets('custom.js')", $dashboard.$admin);
            $this->assertStringNotContainsString('frontend/libs/jquery/dist/jquery.min.js', $dashboard.$admin);
            $this->assertStringNotContainsString('frontend/libs/select2/dist/js/select2.min.js', $dashboard.$admin);
        }

        $assetTagSources = implode('', array_map(
            static fn (string $path): string => (string) file_get_contents($path),
            [
                self::ROOT.'/app/routes.php',
                self::ROOT.'/app/controllers/admin/SettingsController.php',
                self::ROOT.'/app/controllers/admin/DashboardController.php',
            ]
        ));
        $this->assertStringNotContainsString('bootstrap-notify', $assetTagSources);
        $this->assertStringNotContainsString('bootstrap-tagsinput', $assetTagSources);
        $this->assertStringNotContainsString('/compile/1a589a9d55e6fff984', $assetTagSources);
    }

    public function testCompatibilityCodeUsesMaintainedNotificationTagsAndChartApis(): void
    {
        $custom = (string) file_get_contents(self::ROOT.'/public/static/custom.js');
        $charts = (string) file_get_contents(self::ROOT.'/public/static/charts.js');

        $this->assertStringNotContainsString('$.notify', $custom.$charts);
        $this->assertStringNotContainsString('.tagsinput(', $custom);
        $this->assertStringContainsString('new Tagify(', $custom);
        $this->assertStringContainsString('AppNotify.error(', $custom.$charts);
        $this->assertStringContainsString('AppChartConfig.lineOptions', $custom.$charts);
        $this->assertStringContainsString('AppChartConfig.doughnutOptions', $custom.$charts);
        $this->assertStringNotContainsString('cutoutPercentage', $custom.$charts);
        $this->assertStringNotContainsString('xAxes', $custom.$charts);
        $this->assertStringNotContainsString('yAxes', $custom.$charts);

        $stats = (string) file_get_contents(self::ROOT.'/app/controllers/StatsController.php');
        $this->assertSame(5, substr_count($stats, "assets('Chart.min.js'), \"script\")->toFooter()"));
        $this->assertStringNotContainsString("assets('Chart.min.js'), \"script\")->toHeader()", $stats);
    }

    public function testDynamicBrowserLibrariesAreSelfHosted(): void
    {
        $config = (string) file_get_contents(self::ROOT.'/app/config/cdn.php');

        foreach (['ace-builds', 'daterangepicker', 'highlight.js', 'moment', 'spectrum', 'blockadblock'] as $library) {
            $this->assertStringContainsString("frontend/libs/{$library}", $config);
        }
        $this->assertStringNotContainsString('momentjs/latest', $config);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com/ajax/libs/ace', $config);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com/ajax/libs/spectrum', $config);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com/ajax/libs/blockadblock', $config);
    }
}
