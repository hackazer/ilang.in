<?php

declare(strict_types=1);

namespace Tests\Payments;

use Helpers\Payments\Bank;
use PDO;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/app/helpers/payments/Bank.php';

final class CouponConsumptionTimingTest extends TestCase
{
    public function testBankCouponIsConsumedAfterAdministrativeConfirmation(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/controllers/admin/MembershipController.php');

        self::assertIsString($controller);
        $save = strpos($controller, '$payment->save();');
        $consume = strpos($controller, '\Helpers\Payments\Bank::consumeCouponOnConfirmation($payment);');

        self::assertNotFalse($save);
        self::assertNotFalse($consume);
        self::assertGreaterThan($save, $consume);
    }

    public function testBankCheckoutDefersCouponConsumptionAndPersistsConfirmationContext(): void
    {
        $payment = $this->methodSource(
            file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Bank.php'),
            'payment'
        );

        self::assertStringNotContainsString('$coupon->used++', $payment);
        self::assertStringNotContainsString('$coupon->used =', $payment);
        self::assertStringContainsString("'paymentmethod' => 'bank'", $payment);
        self::assertStringContainsString("'subscription_id' => (int) \$sub->id()", $payment);
        self::assertStringContainsString("'coupon_id' => \$coupon ? (int) \$coupon->id : null", $payment);
    }

    public function testBankConfirmationTransactionAppliesCouponConsumptionOnlyOnce(): void
    {
        $database = new PDO('sqlite::memory:');
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->exec('CREATE TABLE confirmation (payment_id INTEGER PRIMARY KEY)');
        $applyCount = 0;
        $alreadyConsumed = static fn(): bool => (int) $database->query('SELECT COUNT(*) FROM confirmation')->fetchColumn() > 0;
        $consume = static function () use ($database, &$applyCount): void {
            $applyCount++;
            $database->exec('INSERT INTO confirmation (payment_id) VALUES (17)');
        };

        self::assertTrue(Bank::applyConfirmationTransaction($database, $alreadyConsumed, $consume));
        self::assertFalse(Bank::applyConfirmationTransaction($database, $alreadyConsumed, $consume));
        self::assertSame(1, $applyCount);
    }

    public function testBankConfirmationHookRequiresCompletedBankPaymentAndRecordsConsumption(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Bank.php');
        $confirmation = $this->methodSource($source, 'consumeCouponOnConfirmation');

        self::assertStringContainsString("!== 'Completed'", $confirmation);
        self::assertStringContainsString("!== 'bank'", $confirmation);
        self::assertStringContainsString("['coupon_consumed_at']", $confirmation);
        self::assertStringContainsString('applyConfirmationTransaction', $confirmation);
    }

    public function testPaypalCheckoutDefersConsumptionButPreservesCouponContextAndPriceUse(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php');
        $payment = $this->methodSource($source, 'payment');

        self::assertStringNotContainsString('$coupon->used++', $payment);
        self::assertStringNotContainsString('$coupon->used =', $payment);
        self::assertStringContainsString('$coupon->discount', $payment);
        self::assertGreaterThanOrEqual(2, substr_count($payment, "'coupon_id' => \$coupon ? (int) \$coupon->id : null"));
    }

    public function testPaypalConsumesCouponInsideExistingIdempotentConfirmationTransactions(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php');
        $lifetime = $this->methodSource($source, 'completeLifetimeOrder');
        $recurring = $this->methodSource($source, 'handleCompletedSubscriptionPayment');
        $activation = $this->methodSource($source, 'completeSubscription');

        self::assertStringContainsString('applyLifetimeTransaction', $lifetime);
        self::assertStringContainsString('consumeCoupon', $lifetime);
        self::assertStringContainsString('applyWebhookTransaction', $recurring);
        self::assertStringContainsString('consumeCoupon', $recurring);
        self::assertStringNotContainsString('consumeCoupon', $activation);
    }

    private function methodSource(string|false $source, string $method): string
    {
        self::assertIsString($source);
        $start = strpos($source, 'function '.$method.'(');
        self::assertNotFalse($start, $method);
        $brace = strpos($source, '{', $start);
        self::assertNotFalse($brace, $method);
        $depth = 0;
        $length = strlen($source);

        for ($offset = $brace; $offset < $length; $offset++) {
            if ($source[$offset] === '{') {
                $depth++;
            } elseif ($source[$offset] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $start, $offset - $start + 1);
                }
            }
        }

        self::fail('Could not isolate '.$method.'.');
    }
}
