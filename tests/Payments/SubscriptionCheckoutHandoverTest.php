<?php

declare(strict_types=1);

namespace Tests\Payments;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once dirname(__DIR__, 2).'/app/traits/Payments.php';
require_once dirname(__DIR__, 2).'/app/controllers/SubscriptionController.php';

final class SubscriptionCheckoutHandoverTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testCheckoutCreationFailureDoesNotCancelExistingSubscription(): void
    {
        $canceled = false;
        $controller = $this->controller();

        $result = $this->invoke($controller, 'executeCheckoutHandover', [
            static fn (): string => 'failed checkout response',
            static function () use (&$canceled): void {
                $canceled = true;
            },
            static fn (): bool => true,
        ]);

        self::assertSame('failed checkout response', $result);
        self::assertFalse($canceled);
    }

    public function testCheckoutExceptionPreservesExistingSubscriptionAndBubbles(): void
    {
        $canceled = false;
        $controller = $this->controller();

        try {
            $this->invoke($controller, 'executeCheckoutHandover', [
                static function (): never {
                    throw new \RuntimeException('checkout creation failed');
                },
                static function () use (&$canceled): void {
                    $canceled = true;
                },
                static fn (): bool => false,
            ]);
            self::fail('Expected checkout creation failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('checkout creation failed', $exception->getMessage());
        }

        self::assertFalse($canceled);
    }

    public function testSuccessfulCheckoutCancelsExistingSubscriptionExactlyOnce(): void
    {
        $cancellations = 0;
        $controller = $this->controller();

        $result = $this->invoke($controller, 'executeCheckoutHandover', [
            static fn (): string => 'created checkout response',
            static function () use (&$cancellations): void {
                $cancellations++;
            },
            static fn (): bool => false,
        ]);

        self::assertSame('created checkout response', $result);
        self::assertSame(1, $cancellations);
    }

    public function testCheckoutAttemptCanOnlyBeClaimedOnceUntilFailureReset(): void
    {
        $controller = $this->controller();
        $token = $this->invoke($controller, 'issueCheckoutAttempt', [7, 12, 'monthly', 1_000]);

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
        self::assertTrue($this->invoke($controller, 'claimCheckoutAttempt', [$token, 7, 12, 'monthly', 1_000]));
        self::assertFalse($this->invoke($controller, 'claimCheckoutAttempt', [$token, 7, 12, 'monthly', 1_000]));

        $this->invoke($controller, 'resetCheckoutAttempt', [$token]);

        self::assertTrue($this->invoke($controller, 'claimCheckoutAttempt', [$token, 7, 12, 'monthly', 1_000]));
    }

    public function testCompletedCheckoutAttemptCannotBeReplayed(): void
    {
        $controller = $this->controller();
        $token = $this->invoke($controller, 'issueCheckoutAttempt', [7, 12, 'yearly', 1_000]);

        self::assertTrue($this->invoke($controller, 'claimCheckoutAttempt', [$token, 7, 12, 'yearly', 1_000]));
        $this->invoke($controller, 'completeCheckoutAttempt', [$token]);

        self::assertFalse($this->invoke($controller, 'claimCheckoutAttempt', [$token, 7, 12, 'yearly', 1_000]));
    }

    public function testExpiredCheckoutAttemptCannotBeClaimed(): void
    {
        $controller = $this->controller();
        $token = $this->invoke($controller, 'issueCheckoutAttempt', [7, 12, 'monthly', 1_000]);

        self::assertFalse($this->invoke($controller, 'claimCheckoutAttempt', [$token, 7, 12, 'monthly', 1_900]));
    }

    public function testCouponLockKeyIsNormalizedDeterministicAndMysqlSafe(): void
    {
        $controller = $this->controller();
        $first = $this->invoke($controller, 'couponLockName', [' Save20 ']);
        $second = $this->invoke($controller, 'couponLockName', ['save20']);

        self::assertSame($first, $second);
        self::assertLessThanOrEqual(64, strlen($first));
    }

    public function testPendingReservationsCountAgainstCouponCapacityWithoutConsumingUse(): void
    {
        $controller = $this->controller();

        self::assertTrue($this->invoke($controller, 'couponHasCapacity', [0, 0, 100]));
        self::assertTrue($this->invoke($controller, 'couponHasCapacity', [2, 5, 2]));
        self::assertFalse($this->invoke($controller, 'couponHasCapacity', [2, 5, 3]));
        self::assertFalse($this->invoke($controller, 'couponHasCapacity', [5, 5, 0]));
    }

    public function testProcessSerializesCouponReservationAndDefersCancellationUntilCreation(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/controllers/SubscriptionController.php');

        self::assertIsString($source);
        $process = $this->methodSource($source, 'process');
        $try = strpos($process, 'try {');
        $processor = strpos($process, '$this->processor($payment');
        $acquire = strpos($process, 'acquireCouponLock');
        $create = strpos($process, 'call_user_func_array($process');
        $cancel = strpos($process, 'cancelExistingSubscription');
        $release = strpos($process, 'releaseCouponLock');

        self::assertIsInt($try);
        self::assertIsInt($processor);
        self::assertIsInt($acquire);
        self::assertIsInt($create);
        self::assertIsInt($cancel);
        self::assertIsInt($release);
        self::assertLessThan($processor, $try);
        self::assertLessThan($create, $acquire);
        self::assertLessThan($cancel, $create);
        self::assertLessThan($release, $cancel);
        self::assertStringContainsString('couponReservations', $process);
        self::assertStringContainsString("(string) (\$request->nowpayments_attempt ?? '')", $process);
        self::assertStringNotContainsString('coupon->used++', $process);
    }

    public function testNowPaymentsKeepsCouponConsumptionAtEntitlementSuccess(): void
    {
        $root = dirname(__DIR__, 2);
        $checkout = file_get_contents($root.'/app/helpers/payments/NowPayments.php');
        $entitlement = file_get_contents($root.'/app/helpers/payments/nowpayments/EntitlementService.php');

        self::assertIsString($checkout);
        self::assertIsString($entitlement);
        $payment = $this->methodSource($checkout, 'payment', true);

        self::assertStringNotContainsString('coupon->used', $payment);
        self::assertStringContainsString("['coupon_id']", $entitlement);
        self::assertStringContainsString('$coupon->used = (int) $coupon->used + 1;', $entitlement);
    }

    private function controller(): object
    {
        return (new ReflectionClass(\Subscription::class))->newInstanceWithoutConstructor();
    }

    /** @param list<mixed> $arguments */
    private function invoke(object $controller, string $method, array $arguments): mixed
    {
        self::assertTrue(method_exists($controller, $method), $method.' must exist.');

        return (new ReflectionClass($controller))->getMethod($method)->invokeArgs($controller, $arguments);
    }

    private function methodSource(string $source, string $method, bool $static = false): string
    {
        $needle = $static ? 'public static function '.$method.'(' : 'public function '.$method.'(';
        $start = strpos($source, $needle);
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
