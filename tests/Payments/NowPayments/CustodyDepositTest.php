<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\CustodyDepositService;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/app/helpers/payments/nowpayments/CustodyDepositService.php';

final class CustodyDepositTest extends TestCase
{
    public function testProviderContextUsesCryptoExpectationAndPreservesFiatTarget(): void
    {
        $context = CustodyDepositService::ledgerContext('99.90', 'USD', '0.0012345', 'BTC');

        self::assertSame('0.0012345', $context['expected_amount']);
        self::assertSame('BTC', $context['price_currency']);
        self::assertSame('0.0012345', $context['pay_amount']);
        self::assertSame('BTC', $context['pay_currency']);
        self::assertSame('99.90', $context['metadata']['fiat_target_amount']);
        self::assertSame('USD', $context['metadata']['fiat_target_currency']);
    }
}
