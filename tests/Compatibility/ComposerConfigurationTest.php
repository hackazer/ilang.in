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

    public function testAbandonedPaypalSdkIsNotRequired(): void
    {
        self::assertArrayNotHasKey('paypal/rest-api-sdk-php', $this->composer['require']);
    }

    public function testPhpunitAndVerificationScriptsAreConfigured(): void
    {
        self::assertSame('^12.5', $this->composer['require-dev']['phpunit/phpunit'] ?? null);
        self::assertSame('@php vendor/bin/phpunit --configuration phpunit.xml', $this->composer['scripts']['test'] ?? null);
        self::assertSame('sh scripts/lint-php.sh', $this->composer['scripts']['lint'] ?? null);
    }

    public function testDirectPackagesTrackCurrentStableCompatibilityLines(): void
    {
        self::assertSame('^8.2', $this->composer['require']['abraham/twitteroauth'] ?? null);
        self::assertSame('^3.1', $this->composer['require']['bacon/bacon-qr-code'] ?? null);
        self::assertSame('^6.0', $this->composer['require']['chillerlan/php-qrcode'] ?? null);
        self::assertArrayNotHasKey('endroid/qr-code', $this->composer['require']);
        self::assertSame('^3.10', $this->composer['require']['monolog/monolog'] ?? null);
        self::assertSame('^9.2', $this->composer['require']['phpfastcache/phpfastcache'] ?? null);
        self::assertSame('^7.1', $this->composer['require']['phpmailer/phpmailer'] ?? null);
        self::assertSame('^21.0', $this->composer['require']['stripe/stripe-php'] ?? null);
    }
}
