<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\Pricing;
use PHPUnit\Framework\TestCase;

foreach (['PricingResult.php', 'Pricing.php'] as $file) {
    $path = dirname(__DIR__, 3).'/app/helpers/payments/nowpayments/'.$file;

    if (is_file($path)) {
        require_once $path;
    }
}

final class PricingTest extends TestCase
{
    public function testBrowserAmountCannotOverrideServerTotal(): void
    {
        $plan = (object) [
            'price_monthly' => '100.00',
            'price_yearly' => '1000.00',
            'price_lifetime' => '2500.00',
        ];
        $coupon = (object) ['id' => 7, 'discount' => 10];
        $tax = (object) ['id' => 4, 'rate' => 11];

        $result = Pricing::forPlan($plan, 'monthly', $coupon, $tax);

        self::assertSame('100.00', $result->subtotal());
        self::assertSame('10.00', $result->discount());
        self::assertSame('9.90', $result->tax());
        self::assertSame('99.90', $result->decimal());
        self::assertSame(7, $result->couponId());
    }

    public function testUnknownTermIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Pricing::forPlan(['price_monthly' => 10], 'weekly');
    }

    public function testNonPositivePriceIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Pricing::forPlan(['price_monthly' => 0], 'monthly');
    }
}
