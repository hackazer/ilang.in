<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\RecurringPlan;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/app/helpers/payments/nowpayments/RecurringPlan.php';

final class RecurringPlanTest extends TestCase
{
    public function testMonthlyAndYearlyDefinitionsAreStableAndModeSpecific(): void
    {
        $callback = 'https://example.test/webhook/nowpayments';
        $monthly = RecurringPlan::define(7, 'Pro', 'monthly', 'email', '19.99', 'USD', $callback);
        $yearly = RecurringPlan::define(7, 'Pro', 'yearly', 'custodial', '199.99', 'USD', $callback);

        self::assertSame(30, $monthly->intervalDays);
        self::assertSame(365, $yearly->intervalDays);
        self::assertNotSame($monthly->mappingKey, $yearly->mappingKey);
        self::assertSame($callback, $monthly->payload()['ipn_callback_url']);
        self::assertSame($monthly->syncHash, RecurringPlan::define(7, 'Pro', 'monthly', 'email', '19.99', 'USD', $callback)->syncHash);
        self::assertNotSame($monthly->syncHash, RecurringPlan::define(7, 'Pro', 'monthly', 'email', '19.99', 'USD', 'https://example.test/other-ipn')->syncHash);
    }

    public function testLifetimeCannotBecomeARecurringPlan(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RecurringPlan::define(7, 'Pro', 'lifetime', 'email', '500.00', 'USD', 'https://example.test/webhook/nowpayments');
    }
}
