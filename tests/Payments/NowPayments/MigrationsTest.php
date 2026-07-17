<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\Migrations;
use PHPUnit\Framework\TestCase;

$migrationFile = dirname(__DIR__, 3).'/app/helpers/payments/nowpayments/Migrations.php';

if (is_file($migrationFile)) {
    require_once $migrationFile;
}

final class MigrationsTest extends TestCase
{
    public function testMigrationDefinesTheDedicatedLedgerTables(): void
    {
        self::assertTrue(class_exists(Migrations::class), 'NOWPayments migrations are not implemented.');
        self::assertSame([
            'nowpayments_transactions',
            'nowpayments_events',
            'nowpayments_plans',
            'nowpayments_customers',
            'nowpayments_outbox',
        ], Migrations::tables());
    }

    public function testMigrationDeclaresEveryIdempotencyConstraint(): void
    {
        $unique = Migrations::uniqueKeys();

        self::assertContains('order_id', $unique['nowpayments_transactions']);
        self::assertContains('idempotency_key', $unique['nowpayments_transactions']);
        self::assertContains('provider_payment_id', $unique['nowpayments_transactions']);
        self::assertContains('provider_cycle_key', $unique['nowpayments_transactions']);
        self::assertNotContains('provider_subscription_id', $unique['nowpayments_transactions']);
        self::assertContains('payload_hash', $unique['nowpayments_events']);
        self::assertContains('mapping_key', $unique['nowpayments_plans']);
        self::assertContains('userid', $unique['nowpayments_customers']);
        self::assertContains('provider_subpartner_id', $unique['nowpayments_customers']);
        self::assertContains('event_key', $unique['nowpayments_outbox']);
        self::assertContains('transaction_id', $unique['nowpayments_outbox']);
    }

    public function testEveryProviderMonetaryColumnUsesExactDecimalStorage(): void
    {
        self::assertSame([
            'nowpayments_transactions' => [
                'expected_amount' => 'DECIMAL(36,18)',
                'pay_amount' => 'DECIMAL(36,18)',
                'received_amount' => 'DECIMAL(36,18)',
                'outcome_amount' => 'DECIMAL(36,18)',
            ],
            'nowpayments_plans' => [
                'amount' => 'DECIMAL(36,18)',
            ],
        ], Migrations::monetaryColumns());
    }

    public function testDefaultConfigurationKeepsPrepaidEnabledAndCustodyDisabled(): void
    {
        $defaults = Migrations::defaultSettings();

        self::assertSame('0', $defaults['enabled']);
        self::assertSame('sandbox', $defaults['environment']);
        self::assertSame('prepaid', $defaults['default_mode']);
        self::assertSame(['prepaid'], $defaults['enabled_modes']);
        self::assertSame('0', $defaults['custodial_enabled']);
    }
}
