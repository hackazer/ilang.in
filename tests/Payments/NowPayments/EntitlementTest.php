<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\EntitlementService;
use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 3).'/app/helpers/payments/nowpayments/';
require_once $root.'Status.php';
require_once $root.'EntitlementService.php';

final class EntitlementTest extends TestCase
{
    public function testRenewalExtendsFromExistingFutureExpiration(): void
    {
        $now = strtotime('2026-07-16 00:00:00 UTC');

        self::assertSame(
            '2026-09-01 00:00:00',
            EntitlementService::expiry('monthly', '2026-08-01 00:00:00', $now)
        );
    }

    public function testExpiredPlanExtendsFromPaymentTime(): void
    {
        $now = strtotime('2026-07-16 00:00:00 UTC');

        self::assertSame('2027-07-16 00:00:00', EntitlementService::expiry('yearly', '2026-01-01 00:00:00', $now));
    }

    public function testCustodyDepositNeverGrantsPlanEntitlement(): void
    {
        self::assertFalse(EntitlementService::shouldApply('custodial_deposit', 'paid'));
        self::assertTrue(EntitlementService::shouldApply('custodial', 'paid'));
        self::assertFalse(EntitlementService::shouldApply('prepaid', 'confirming'));
    }

    public function testDecimalComparisonNeverUsesFloatTolerance(): void
    {
        self::assertTrue(EntitlementService::decimalEquals('10.00', '10'));
        self::assertFalse(EntitlementService::decimalEquals('10.000000000000000001', '10'));
        self::assertFalse(EntitlementService::decimalEquals('0.100000000000000001', 0.1));
    }

    public function testPendingSubscriptionInitializesWithoutDoubleCounting(): void
    {
        self::assertSame(
            '10',
            EntitlementService::nextSubscriptionAmount('10.000000000000000000', 'Pending', '10')
        );
    }

    public function testActiveSubscriptionPreservesItsCumulativeAmount(): void
    {
        self::assertSame(
            '30.25',
            EntitlementService::nextSubscriptionAmount('20.15', 'Active', '10.10')
        );
    }
}
