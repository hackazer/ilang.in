<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Helper;
use PHPUnit\Framework\TestCase;

final class NonceTest extends TestCase
{
    public function testNonceIsBoundToActionSessionAndSecret(): void
    {
        $time = 1_800_000_000;
        $nonce = Helper::nonce('billing.cancel', 60, $time, 'secret-a', 'session-a');

        self::assertSame($nonce, Helper::nonce('billing.cancel', 60, $time, 'secret-a', 'session-a'));
        self::assertNotSame($nonce, Helper::nonce('account.delete', 60, $time, 'secret-a', 'session-a'));
        self::assertNotSame($nonce, Helper::nonce('billing.cancel', 60, $time, 'secret-a', 'session-b'));
        self::assertNotSame($nonce, Helper::nonce('billing.cancel', 60, $time, 'secret-b', 'session-a'));
    }

    public function testValidationUsesCurrentAndPreviousWindowOnly(): void
    {
        $duration = 2;
        $windowSeconds = 60;
        $time = 1_800_000_020;
        $nonce = Helper::nonce('tools', $duration, $time, 'secret', 'session');

        self::assertTrue(Helper::validateNonce($nonce, 'tools', $duration, $time, 'secret', 'session'));
        self::assertTrue(Helper::validateNonce($nonce, 'tools', $duration, $time + $windowSeconds, 'secret', 'session'));
        self::assertFalse(Helper::validateNonce($nonce, 'tools', $duration, $time + ($windowSeconds * 2), 'secret', 'session'));
        self::assertFalse(Helper::validateNonce($nonce, 'other', $duration, $time, 'secret', 'session'));
    }
}
