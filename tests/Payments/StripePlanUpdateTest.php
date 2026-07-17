<?php

declare(strict_types=1);

namespace Tests\Payments;

use PHPUnit\Framework\TestCase;

final class StripePlanUpdateTest extends TestCase
{
    public function testPlanUpdateInitializesProductBeforeAnyOptionalPriceRefresh(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Stripe.php');

        self::assertIsString($source);
        $start = strpos($source, 'public static function updateplan');
        $end = strpos($source, 'public static function syncplan', $start ?: 0);
        self::assertIsInt($start);
        self::assertIsInt($end);
        $method = substr($source, $start, $end - $start);
        self::assertStringContainsString("json_decode((string) \$plan->data, true)", $method);
        self::assertStringContainsString("\$productid = \$providerData['stripe'] ?? null;", $method);
        self::assertStringNotContainsString('$productid = $oldplan->product;', substr($method, 0, (int) strpos($method, 'if($request->price_monthly')));
    }
}
