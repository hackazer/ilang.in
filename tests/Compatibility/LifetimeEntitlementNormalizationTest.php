<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class LifetimeEntitlementNormalizationTest extends TestCase
{
    public function testStripeLifetimeCheckoutUsesTwentyYearExpiryAcrossSubscriptionPaymentAndUser(): void
    {
        $source = $this->source('app/helpers/payments/Stripe.php');

        self::assertStringContainsString("\$sub->expiry = Helper::dtime('+20 years');", $source);
        self::assertStringContainsString(
            "\$user->expiration = \$type == \"lifetime\" ? Helper::dtime('+20 years') : Helper::dtime();",
            $source
        );
        self::assertStringContainsString('$payment->expiry = $user->expiration;', $source);
        self::assertStringNotContainsString('+10 year', $source);
    }

    public function testStripeLifetimeWebhookUsesTwentyYearExpiryAcrossPaymentSubscriptionAndUser(): void
    {
        $source = $this->source('app/helpers/payments/Stripe.php');

        self::assertStringContainsString('strtotime("+20 years", $e->created)', $source);
        self::assertStringContainsString('$payment->expiry =  $new_expiry;', $source);
        self::assertStringContainsString('$subscription->expiry = $new_expiry;', $source);
        self::assertStringContainsString('$user->expiration = $new_expiry;', $source);
    }

    public function testBankLifetimePaymentUsesTwentyYearExpiry(): void
    {
        $source = $this->source('app/helpers/payments/Bank.php');

        self::assertStringContainsString('strtotime("+20 years")', $source);
        self::assertStringContainsString('$payment->expiry =  $new_expiry;', $source);
        self::assertStringNotContainsString('+10 year', $source);
    }

    public function testApiLifetimeSubscriptionUsesTwentyYearUserExpiry(): void
    {
        $source = $this->source('app/controllers/api/PlansController.php');

        self::assertStringContainsString("\$user->expiration = Helper::dtime('+20 years');", $source);
        self::assertStringNotContainsString('+10 year', $source);
    }

    public function testMonthlyAndYearlyExpiryModifiersRemainUnchanged(): void
    {
        $stripe = $this->source('app/helpers/payments/Stripe.php');
        $bank = $this->source('app/helpers/payments/Bank.php');
        $plans = $this->source('app/controllers/api/PlansController.php');

        self::assertStringContainsString('strtotime("+1 year", $e->created)', $stripe);
        self::assertStringContainsString('strtotime("+1 month", $e->created)', $stripe);
        self::assertStringContainsString('strtotime("+1 year")', $bank);
        self::assertStringContainsString('strtotime("+1 month")', $bank);
        self::assertStringContainsString("Helper::dtime('+1 year')", $plans);
        self::assertStringContainsString("Helper::dtime('+1 month')", $plans);
    }

    private function source(string $path): string
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/'.$path);

        self::assertIsString($source);

        return $source;
    }
}
