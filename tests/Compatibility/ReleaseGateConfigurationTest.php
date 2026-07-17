<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class ReleaseGateConfigurationTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testNpmTestRunsEveryCompatibilityJavaScriptTest(): void
    {
        $package = $this->json('package.json');

        self::assertSame(
            'node --test tests/Compatibility/*.test.js',
            $package['scripts']['test:browser'] ?? null,
        );
        self::assertStringContainsString('npm run test:browser', (string) ($package['scripts']['test'] ?? ''));
    }

    public function testBrowserCiUsesLockedNodeAndRunsEveryRequiredGate(): void
    {
        $workflow = $this->contents('.github/workflows/php.yml');

        self::assertStringContainsString("browser:\n", $workflow);
        self::assertMatchesRegularExpression('/uses: actions\/setup-node@[0-9a-f]{40} # v7\.0\.0/', $workflow);
        self::assertStringContainsString("node-version: '24.18.0'", $workflow);
        self::assertStringContainsString('npm ci --ignore-scripts --no-audit --no-fund', $workflow);
        self::assertStringContainsString('npm audit --audit-level=high', $workflow);
        self::assertStringContainsString('run: npm test', $workflow);
        self::assertStringContainsString('run: npm run check:browser', $workflow);
    }

    public function testPhpLintUsesTrackedFirstPartyFilesAndDocumentsRuntimeExclusions(): void
    {
        $script = $this->contents('scripts/lint-php.sh');

        self::assertStringContainsString("git ls-files '*.php'", $script);
        self::assertStringContainsString('storage/plugins/*', $script);
        self::assertStringContainsString('storage/addons/*', $script);
        self::assertStringContainsString('storage/cache/*', $script);
        self::assertStringNotContainsString('find app core public storage/themes', $script);
    }

    public function testDependencyReleasePolicyNamesManagedAssetsAndSupportedRuntimeMatrix(): void
    {
        $package = $this->json('package.json');
        $policy = $package['dependencyReleasePolicy'] ?? null;

        self::assertIsArray($policy);
        self::assertContains('asset:editor-adapter', $policy['managedFirstPartyAssets'] ?? []);
        self::assertContains('bootstrap', $policy['allowedParallelRepresentations'] ?? []);
        self::assertContains('jquery', $policy['allowedDuplicatePackages'] ?? []);
        self::assertSame([], $policy['compatibilityHolds'] ?? null);
        self::assertStringContainsString('actively supported', $policy['runtimeMatrix']['phpunit'] ?? '');
    }

    public function testReleaseGateValidatesInventoryAgainstExplicitPolicy(): void
    {
        $script = $this->contents('scripts/verify-release.sh');

        self::assertStringContainsString('dependencyReleasePolicy', $script);
        self::assertStringContainsString('Unapproved dependency inventory findings', $script);
        self::assertStringContainsString('npm run check:browser', $script);
    }

    private function contents(string $relativePath): string
    {
        $contents = file_get_contents($this->root.'/'.$relativePath);
        self::assertNotFalse($contents);

        return $contents;
    }

    private function json(string $relativePath): array
    {
        return json_decode($this->contents($relativePath), true, 512, JSON_THROW_ON_ERROR);
    }
}
