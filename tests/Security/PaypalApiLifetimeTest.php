<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\Payments\PaypalApi;
use Helpers\Payments\Paypal\ApiException;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php';

final class PaypalApiLifetimeTest extends TestCase
{
    public function testLifetimeCallbackClaimsCannotOverrideTheStoredIntent(): void
    {
        $request = (object) [
            'token' => 'ORDER-TRUSTED-1',
            'type' => 'monthly',
            'planid' => 999,
            'userid' => 666,
            'amount' => '0.01',
            'currency' => 'EUR',
        ];
        $lookups = [];

        $intent = PaypalApi::lifetimeIntentForCallback(
            $request,
            static function (string $orderId) use (&$lookups): object {
                $lookups[] = $orderId;

                return self::pendingSubscription();
            }
        );

        self::assertSame(['ORDER-TRUSTED-1'], $lookups);
        self::assertSame([
            'order_id' => 'ORDER-TRUSTED-1',
            'user_id' => 42,
            'plan_id' => 7,
            'term' => 'lifetime',
            'amount' => '125.50',
            'currency' => 'USD',
        ], $intent);
    }

    public function testCompletedCaptureUsesOnlyTheTrustedLifetimeIntent(): void
    {
        $capture = PaypalApi::validateLifetimeCapture(
            $this->completedOrder(),
            $this->trustedIntent()
        );

        self::assertSame([
            'order_id' => 'ORDER-TRUSTED-1',
            'capture_id' => 'CAPTURE-1',
            'user_id' => 42,
            'plan_id' => 7,
            'term' => 'lifetime',
            'amount' => '125.50',
            'currency' => 'USD',
        ], $capture);
    }

    #[DataProvider('invalidCaptureProvider')]
    public function testLifetimeCaptureMustMatchTheStoredOrderStateAmountAndCurrency(array $order): void
    {
        $this->expectException(ApiException::class);

        PaypalApi::validateLifetimeCapture($order, $this->trustedIntent());
    }

    public static function invalidCaptureProvider(): array
    {
        $valid = self::completedOrderFixture();

        $wrongOrder = $valid;
        $wrongOrder['id'] = 'ORDER-ATTACKER';

        $wrongOrderState = $valid;
        $wrongOrderState['status'] = 'APPROVED';

        $wrongCaptureState = $valid;
        $wrongCaptureState['purchase_units'][0]['payments']['captures'][0]['status'] = 'PENDING';

        $wrongAmount = $valid;
        $wrongAmount['purchase_units'][0]['payments']['captures'][0]['amount']['value'] = '0.01';

        $wrongCurrency = $valid;
        $wrongCurrency['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] = 'EUR';

        return [
            'different order ID' => [$wrongOrder],
            'order not completed' => [$wrongOrderState],
            'capture not completed' => [$wrongCaptureState],
            'different amount' => [$wrongAmount],
            'different currency' => [$wrongCurrency],
        ];
    }

    public function testLifetimeFulfillmentIsCommittedOnce(): void
    {
        $database = $this->database();
        $applyCount = 0;
        $alreadyProcessed = static fn(): bool => (int) $database->query('SELECT COUNT(*) FROM fulfillment')->fetchColumn() > 0;
        $apply = static function () use ($database, &$applyCount): void {
            $applyCount++;
            $database->exec("INSERT INTO fulfillment (order_id) VALUES ('ORDER-TRUSTED-1')");
        };

        self::assertTrue(PaypalApi::applyLifetimeTransaction($database, $alreadyProcessed, $apply));
        self::assertFalse(PaypalApi::applyLifetimeTransaction($database, $alreadyProcessed, $apply));
        self::assertSame(1, $applyCount);
        self::assertSame(1, (int) $database->query('SELECT COUNT(*) FROM fulfillment')->fetchColumn());
    }

    public function testLifetimeFulfillmentRollsBackEveryMutationOnFailure(): void
    {
        $database = $this->database();

        try {
            PaypalApi::applyLifetimeTransaction(
                $database,
                static fn(): bool => false,
                static function () use ($database): void {
                    $database->exec("INSERT INTO fulfillment (order_id) VALUES ('ORDER-TRUSTED-1')");
                    throw new RuntimeException('simulated user update failure');
                }
            );
            self::fail('The simulated fulfillment failure was not raised.');
        } catch (RuntimeException $exception) {
            self::assertSame('simulated user update failure', $exception->getMessage());
        }

        self::assertSame(0, (int) $database->query('SELECT COUNT(*) FROM fulfillment')->fetchColumn());
    }

    public function testLifetimeCheckoutStoresTheImmutableIntentBeforeRedirect(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php');

        self::assertIsString($source);
        $lifetimeCheckoutStart = strpos($source, "if(\$type == 'lifetime')");
        $recurringCheckoutStart = strpos($source, '} else {', $lifetimeCheckoutStart);
        self::assertNotFalse($lifetimeCheckoutStart);
        self::assertNotFalse($recurringCheckoutStart);
        $lifetimeCheckout = substr($source, $lifetimeCheckoutStart, $recurringCheckoutStart - $lifetimeCheckoutStart);

        self::assertStringContainsString("'return_url' => url('webhook/paypal?success=true')", $lifetimeCheckout);
        self::assertStringNotContainsString('planid=', $lifetimeCheckout);
        self::assertStringContainsString("\$sub->tid = \$order['id'];", $lifetimeCheckout);
        self::assertStringContainsString("'intent' => [", $lifetimeCheckout);
        self::assertLessThan(
            strpos($lifetimeCheckout, 'header("Location: {$approvalUrl}");'),
            strpos($lifetimeCheckout, '$sub->save();')
        );
    }

    public function testLifetimeCompletionUsesLockingTrustedStateAndNoCallbackClaims(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php');

        self::assertIsString($source);
        self::assertMatchesRegularExpression(
            '/private static function completeLifetimeOrder\(.*?\n    }\n\n    private static function/s',
            $source
        );
        preg_match(
            '/private static function completeLifetimeOrder\(.*?\n    }\n\n    private static function/s',
            $source,
            $matches
        );
        $completion = $matches[0];

        foreach (['planid', 'userid', 'amount', 'currency', 'type', 'paymentId'] as $claim) {
            self::assertStringNotContainsString('$request->'.$claim, $completion);
        }

        self::assertStringContainsString('lifetimeIntentForCallback', $completion);
        self::assertStringContainsString('captureOrFetchLifetimeOrder', $completion);
        self::assertStringContainsString('validateLifetimeCapture', $completion);
        self::assertStringContainsString('applyLifetimeTransaction', $completion);
        self::assertStringContainsString('withLifetimeOrderLock', $completion);
    }

    public function testAlreadyCapturedOrderIsRecoveredWithoutRecapturing(): void
    {
        $captureCalls = 0;
        $completed = $this->completedOrder();

        $order = PaypalApi::captureOrFetchLifetimeOrder(
            'ORDER-TRUSTED-1',
            static fn(string $orderId): array => $completed,
            static function () use (&$captureCalls): array {
                $captureCalls++;
                return [];
            }
        );

        self::assertSame($completed, $order);
        self::assertSame(0, $captureCalls);
    }

    public function testApprovedOrderIsCapturedOnce(): void
    {
        $captureCalls = 0;
        $completed = $this->completedOrder();

        $order = PaypalApi::captureOrFetchLifetimeOrder(
            'ORDER-TRUSTED-1',
            static fn(string $orderId): array => [
                'id' => $orderId,
                'status' => 'APPROVED',
            ],
            static function (string $orderId) use (&$captureCalls, $completed): array {
                $captureCalls++;
                self::assertSame('ORDER-TRUSTED-1', $orderId);
                return $completed;
            }
        );

        self::assertSame($completed, $order);
        self::assertSame(1, $captureCalls);
    }

    private function completedOrder(): array
    {
        return self::completedOrderFixture();
    }

    private static function completedOrderFixture(): array
    {
        return [
            'id' => 'ORDER-TRUSTED-1',
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'custom_id' => 'userid:666',
                'payments' => [
                    'captures' => [[
                        'id' => 'CAPTURE-1',
                        'status' => 'COMPLETED',
                        'amount' => [
                            'value' => '125.50',
                            'currency_code' => 'USD',
                        ],
                    ]],
                ],
            ]],
        ];
    }

    private function trustedIntent(): array
    {
        return [
            'order_id' => 'ORDER-TRUSTED-1',
            'user_id' => 42,
            'plan_id' => 7,
            'term' => 'lifetime',
            'amount' => '125.50',
            'currency' => 'USD',
        ];
    }

    private function database(): PDO
    {
        $database = new PDO('sqlite::memory:');
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->exec('CREATE TABLE fulfillment (order_id TEXT PRIMARY KEY)');

        return $database;
    }

    private static function pendingSubscription(): object
    {
        return (object) [
            'tid' => 'ORDER-TRUSTED-1',
            'userid' => 42,
            'planid' => 7,
            'plan' => 'lifetime',
            'status' => 'Pending',
            'amount' => '125.50',
            'data' => json_encode([
                'paymentmethod' => 'PaypalApi',
                'intent' => [
                    'order_id' => 'ORDER-TRUSTED-1',
                    'user_id' => 42,
                    'plan_id' => 7,
                    'term' => 'lifetime',
                    'amount' => '125.50',
                    'currency' => 'USD',
                ],
            ], JSON_THROW_ON_ERROR),
        ];
    }
}
