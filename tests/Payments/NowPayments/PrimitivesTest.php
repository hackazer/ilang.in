<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\Order;
use Helpers\Payments\Nowpayments\Readiness;
use Helpers\Payments\Nowpayments\Signature;
use Helpers\Payments\Nowpayments\Status;
use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 3);

foreach (['Signature', 'Status', 'Order', 'ReadinessResult', 'Readiness'] as $classFile) {
    $file = $root.'/app/helpers/payments/nowpayments/'.$classFile.'.php';

    if (is_file($file)) {
        require_once $file;
    }
}

final class PrimitivesTest extends TestCase
{
    public function testNestedPayloadIsCanonicalizedBeforeSigning(): void
    {
        self::assertTrue(class_exists(Signature::class), 'Signature primitive is not implemented.');

        $payload = [
            'payment_status' => 'finished',
            'payment_id' => 42,
            'nested' => ['z' => 2, 'a' => 1],
        ];
        $canonical = '{"nested":{"a":1,"z":2},"payment_id":42,"payment_status":"finished"}';
        $signature = hash_hmac('sha512', $canonical, 'secret');

        self::assertSame($canonical, Signature::canonicalJson($payload));
        self::assertTrue(Signature::verify($payload, strtoupper($signature), 'secret'));
        self::assertFalse(Signature::verify($payload, str_repeat('0', 128), 'secret'));
    }

    public function testProviderStatusesNormalizeWithoutGrantingEarlyAccess(): void
    {
        self::assertSame(Status::PENDING, Status::normalize('waiting'));
        self::assertSame(Status::CONFIRMING, Status::normalize('confirmed'));
        self::assertSame(Status::CONFIRMING, Status::normalize('sending'));
        self::assertSame(Status::PARTIAL, Status::normalize('partially_paid'));
        self::assertSame(Status::PAID, Status::normalize('finished'));
        self::assertSame(Status::PAID, Status::normalize('PAID'));
        self::assertSame(Status::PENDING, Status::normalize('WAITING_PAY'));
        self::assertSame(Status::FAILED, Status::normalize('unknown-provider-state'));
        self::assertFalse(Status::supported('unknown-provider-state'));
        self::assertTrue(Status::supported('finished'));
    }

    public function testTerminalStatusCannotMoveBackToPending(): void
    {
        self::assertTrue(Status::canTransition(Status::PENDING, Status::PAID));
        self::assertTrue(Status::canTransition(Status::CONFIRMING, Status::PARTIAL));
        self::assertFalse(Status::canTransition(Status::PAID, Status::PENDING));
        self::assertFalse(Status::canTransition(Status::EXPIRED, Status::CONFIRMING));
        self::assertTrue(Status::canTransition(Status::PAID, Status::PAID));
    }

    public function testOrderAndIdempotencyKeysAreStableForOneAttempt(): void
    {
        $first = Order::fromAttempt(17, 3, 'monthly', 'prepaid', 'attempt-123');
        $second = Order::fromAttempt(17, 3, 'monthly', 'prepaid', 'attempt-123');

        self::assertSame($first->id(), $second->id());
        self::assertSame($first->idempotencyKey(), $second->idempotencyKey());
        self::assertMatchesRegularExpression('/^np-[a-z0-9-]{10,64}$/', $first->id());
        self::assertSame(64, strlen($first->idempotencyKey()));
    }

    public function testCustodialModeRequiresEverySensitiveCredential(): void
    {
        $result = Readiness::custodial([
            'api_key' => 'api-key',
            'ipn_secret' => 'ipn-secret',
        ]);

        self::assertFalse($result->ready());
        self::assertContains('dashboard_email', $result->missing());
        self::assertContains('dashboard_password', $result->missing());
        self::assertContains('callback_url', $result->missing());

        $ready = Readiness::custodial([
            'api_key' => 'api-key',
            'ipn_secret' => 'ipn-secret',
            'dashboard_email' => 'merchant@example.test',
            'dashboard_password' => 'encrypted-value',
            'callback_url' => 'https://example.test/webhook/nowpayments',
            'settlement_currency' => 'usdttrc20',
        ]);

        self::assertTrue($ready->ready());
        self::assertSame([], $ready->missing());
    }
}
