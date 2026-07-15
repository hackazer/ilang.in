<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class ComposerConfigurationTest extends TestCase
{
    private array $composer;

    protected function setUp(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/composer.json');
        self::assertNotFalse($contents);

        $this->composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testComposerDeclaresPhp83AsMinimumRuntime(): void
    {
        self::assertSame('^8.3', $this->composer['require']['php'] ?? null);
    }

    public function testUnsupportedFacebookSdkIsNotRequired(): void
    {
        self::assertArrayNotHasKey('facebook/graph-sdk', $this->composer['require']);
    }

    public function testAbandonedGoogleAuthenticatorIsNotRequired(): void
    {
        self::assertArrayNotHasKey('sonata-project/google-authenticator', $this->composer['require']);
    }

    public function testPhpunitAndVerificationScriptsAreConfigured(): void
    {
        self::assertSame('^11.5', $this->composer['require-dev']['phpunit/phpunit'] ?? null);
        self::assertSame('@php vendor/bin/phpunit --configuration phpunit.xml', $this->composer['scripts']['test'] ?? null);
        self::assertSame('sh scripts/lint-php.sh', $this->composer['scripts']['lint'] ?? null);
    }
}
