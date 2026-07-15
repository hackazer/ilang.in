<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use DateTimeImmutable;
use Helpers\GoogleAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GoogleAuthenticatorTest extends TestCase
{
    #[DataProvider('rfc6238Sha1Vectors')]
    public function testGeneratesRfc6238Sha1Codes(int $timestamp, string $expected): void
    {
        $authenticator = new GoogleAuthenticator(8);

        self::assertSame(
            $expected,
            $authenticator->getCode('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $timestamp)
        );
    }

    public static function rfc6238Sha1Vectors(): array
    {
        return [
            [59, '94287082'],
            [1111111109, '07081804'],
            [1111111111, '14050471'],
            [1234567890, '89005924'],
            [2000000000, '69279037'],
            [20000000000, '65353130'],
        ];
    }

    public function testGeneratedSecretPreservesDefaultBase32Format(): void
    {
        $secret = (new GoogleAuthenticator())->generateSecret();

        self::assertMatchesRegularExpression('/^[A-Z2-7]{16}$/', $secret);
    }

    public function testCheckCodeAcceptsConfiguredTimeWindow(): void
    {
        $time = new DateTimeImmutable('@1111111111');
        $authenticator = new GoogleAuthenticator(8, 10, $time);
        $previousCode = $authenticator->getCode(
            'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            $time->getTimestamp() - 30
        );

        self::assertTrue($authenticator->checkCode(
            'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            $previousCode,
            1
        ));
        self::assertFalse($authenticator->checkCode(
            'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            $previousCode,
            0
        ));
    }
}
