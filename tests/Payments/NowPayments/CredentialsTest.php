<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\Credentials;
use PHPUnit\Framework\TestCase;

$credentialsFile = dirname(__DIR__, 3).'/app/helpers/payments/nowpayments/Credentials.php';

if (is_file($credentialsFile)) {
    require_once $credentialsFile;
}

final class CredentialsTest extends TestCase
{
    public function testBlankSecretInputsPreserveStoredCiphertext(): void
    {
        $existing = [
            'api_key_encrypted' => 'cipher-api',
            'ipn_secret_encrypted' => 'cipher-ipn',
            'dashboard_password_encrypted' => 'cipher-password',
        ];

        $stored = Credentials::prepareForStorage([
            'enabled' => '1',
            'api_key' => '',
            'ipn_secret' => '',
            'dashboard_password' => '',
        ], $existing, static fn (string $secret): string => 'encrypted-'.$secret);

        self::assertSame('cipher-api', $stored['api_key_encrypted']);
        self::assertSame('cipher-ipn', $stored['ipn_secret_encrypted']);
        self::assertSame('cipher-password', $stored['dashboard_password_encrypted']);
        self::assertArrayNotHasKey('api_key', $stored);
        self::assertArrayNotHasKey('ipn_secret', $stored);
        self::assertArrayNotHasKey('dashboard_password', $stored);
    }

    public function testNewSecretsAreEncryptedAndRuntimeValuesAreDecrypted(): void
    {
        $stored = Credentials::prepareForStorage([
            'api_key' => 'api-secret',
            'ipn_secret' => 'ipn-secret',
            'dashboard_password' => 'dashboard-secret',
        ], [], static fn (string $secret): string => base64_encode($secret));

        $runtime = Credentials::runtime(
            $stored,
            static fn (string $ciphertext): string => (string) base64_decode($ciphertext, true)
        );

        self::assertSame('api-secret', $runtime['api_key']);
        self::assertSame('ipn-secret', $runtime['ipn_secret']);
        self::assertSame('dashboard-secret', $runtime['dashboard_password']);
    }

    public function testSecretValuesAreNeverIncludedInRenderableSettings(): void
    {
        $renderable = Credentials::renderable([
            'api_key_encrypted' => 'cipher-api',
            'ipn_secret_encrypted' => 'cipher-ipn',
            'dashboard_password_encrypted' => 'cipher-password',
        ]);

        self::assertTrue($renderable['api_key_configured']);
        self::assertTrue($renderable['ipn_secret_configured']);
        self::assertTrue($renderable['dashboard_password_configured']);
        self::assertArrayNotHasKey('api_key_encrypted', $renderable);
        self::assertArrayNotHasKey('ipn_secret_encrypted', $renderable);
        self::assertArrayNotHasKey('dashboard_password_encrypted', $renderable);
    }
}
