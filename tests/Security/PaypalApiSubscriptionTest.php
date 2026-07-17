<?php

declare(strict_types=1);

namespace Helpers\Payments {
    function config(?string $key = null): mixed
    {
        $config = $GLOBALS['paypal_api_test_config'] ?? [];

        return $key === null ? (object) $config : ($config[$key] ?? false);
    }
}

namespace Tests\Security {

use Helpers\Payments\Paypal\Client;
use Helpers\Payments\PaypalApi;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php';

final class PaypalApiSubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['paypal_api_test_config'] = ['currency' => 'USD'];
    }

    #[DataProvider('recurringTerms')]
    public function testRecurringPlansRenewUntilTheyAreCancelled(string $term, string $frequency): void
    {
        $requests = [];
        $responses = [
            $this->jsonResponse(['access_token' => 'token', 'expires_in' => 3600]),
            $this->jsonResponse(['id' => 'PRODUCT-1']),
            $this->jsonResponse(['id' => 'PLAN-1', 'status' => 'ACTIVE']),
        ];
        $client = new Client(
            static function (string $method, string $url, array $headers, ?string $body, array $options) use (&$requests, &$responses): array {
                $requests[] = compact('method', 'url', 'headers', 'body', 'options');
                $response = array_shift($responses);
                self::assertIsArray($response, 'Unexpected PayPal request.');

                return $response;
            },
            'client-id',
            'client-secret',
            true
        );
        $plan = (object) [
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price_monthly' => '12.50',
            'price_yearly' => '120.00',
        ];

        self::assertSame('PLAN-1', PaypalApi::createSinglePlan($plan, $term, null, null, $client));

        $payload = json_decode($requests[2]['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($frequency, $payload['billing_cycles'][0]['frequency']['interval_unit']);
        self::assertSame(0, $payload['billing_cycles'][0]['total_cycles']);
    }

    public static function recurringTerms(): array
    {
        return [
            'monthly' => ['monthly', 'MONTH'],
            'yearly' => ['yearly', 'YEAR'],
        ];
    }

    public function testCompletedSubscriptionSaleIsNormalizedFromPaypalFields(): void
    {
        $event = [
            'id' => 'WH-SALE-1',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'create_time' => '2026-07-17T03:00:00Z',
            'resource' => [
                'id' => 'SALE-1',
                'state' => 'completed',
                'billing_agreement_id' => 'I-SUB-1',
                'amount' => ['total' => '12.50', 'currency' => 'usd'],
            ],
        ];

        self::assertSame([
            'event_id' => 'WH-SALE-1',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'kind' => 'payment_completed',
            'sale_id' => 'SALE-1',
            'subscription_id' => 'I-SUB-1',
            'amount' => '12.50',
            'currency' => 'USD',
            'occurred_at' => '2026-07-17T03:00:00Z',
        ], PaypalApi::normalizeSubscriptionWebhookEvent($event));
    }

    #[DataProvider('reversalEvents')]
    public function testRefundAndReversalEventsResolveTheOriginalSale(string $eventType, array $resource, string $kind): void
    {
        $event = [
            'id' => 'WH-REVERSAL-1',
            'event_type' => $eventType,
            'create_time' => '2026-07-17T04:00:00Z',
            'resource' => $resource,
        ];

        $action = PaypalApi::normalizeSubscriptionWebhookEvent($event);

        self::assertIsArray($action);
        self::assertSame($kind, $action['kind']);
        self::assertSame('SALE-1', $action['sale_id']);
        self::assertSame('12.50', $action['amount']);
        self::assertSame('USD', $action['currency']);
    }

    public static function reversalEvents(): array
    {
        return [
            'refund' => [
                'PAYMENT.SALE.REFUNDED',
                [
                    'id' => 'REFUND-1',
                    'sale_id' => 'SALE-1',
                    'state' => 'completed',
                    'amount' => ['total' => '12.50', 'currency' => 'USD'],
                ],
                'payment_refunded',
            ],
            'reversal' => [
                'PAYMENT.SALE.REVERSED',
                [
                    'id' => 'SALE-1',
                    'state' => 'reversed',
                    'billing_agreement_id' => 'I-SUB-1',
                    'amount' => ['total' => '12.50', 'currency' => 'USD'],
                ],
                'payment_reversed',
            ],
        ];
    }

    #[DataProvider('subscriptionStateEvents')]
    public function testSubscriptionStateEventsMapWithoutGrantingPaymentEntitlement(string $eventType, string $status): void
    {
        $action = PaypalApi::normalizeSubscriptionWebhookEvent([
            'id' => 'WH-STATE-1',
            'event_type' => $eventType,
            'create_time' => '2026-07-17T05:00:00Z',
            'resource' => ['id' => 'I-SUB-1'],
        ]);

        self::assertIsArray($action);
        self::assertSame('subscription_status', $action['kind']);
        self::assertSame('I-SUB-1', $action['subscription_id']);
        self::assertSame($status, $action['status']);
    }

    public static function subscriptionStateEvents(): array
    {
        return [
            'activated' => ['BILLING.SUBSCRIPTION.ACTIVATED', 'Active'],
            'payment failed' => ['BILLING.SUBSCRIPTION.PAYMENT.FAILED', 'Past Due'],
            'suspended' => ['BILLING.SUBSCRIPTION.SUSPENDED', 'Suspended'],
            'cancelled' => ['BILLING.SUBSCRIPTION.CANCELLED', 'Canceled'],
            'expired' => ['BILLING.SUBSCRIPTION.EXPIRED', 'Expired'],
        ];
    }

    public function testPaypalWebhookTransactionAppliesAnEventOnlyOnce(): void
    {
        $database = new PDO('sqlite::memory:');
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->exec('CREATE TABLE webhook_event (event_id TEXT PRIMARY KEY)');
        $applyCount = 0;
        $alreadyProcessed = static fn(): bool => (int) $database->query("SELECT COUNT(*) FROM webhook_event WHERE event_id = 'WH-SALE-1'")->fetchColumn() > 0;
        $apply = static function () use ($database, &$applyCount): void {
            $applyCount++;
            $database->exec("INSERT INTO webhook_event (event_id) VALUES ('WH-SALE-1')");
        };

        self::assertTrue(PaypalApi::applyWebhookTransaction($database, $alreadyProcessed, $apply));
        self::assertFalse(PaypalApi::applyWebhookTransaction($database, $alreadyProcessed, $apply));
        self::assertSame(1, $applyCount);
    }

    public function testLifetimeEntitlementUsesTheTwentyYearProjectSentinel(): void
    {
        $now = strtotime('2026-07-17 00:00:00');

        self::assertSame(
            '2048-02-01 00:00:00',
            PaypalApi::entitlementExpiry('lifetime', '2028-02-01 00:00:00', $now)
        );
    }

    public function testOnlyAFullReversalOfTheCurrentPaypalEntitlementRevokesAccess(): void
    {
        self::assertFalse(PaypalApi::reversalRevokesEntitlement(
            'payment_refunded',
            5.00,
            12.50,
            7,
            7,
            '2026-08-17 00:00:00',
            '2026-08-17 00:00:00'
        ));
        self::assertTrue(PaypalApi::reversalRevokesEntitlement(
            'payment_refunded',
            12.50,
            12.50,
            7,
            7,
            '2026-08-17 00:00:00',
            '2026-08-17 00:00:00'
        ));
        self::assertFalse(PaypalApi::reversalRevokesEntitlement(
            'payment_reversed',
            12.50,
            12.50,
            7,
            9,
            '2026-08-17 00:00:00',
            '2026-08-17 00:00:00'
        ));
        self::assertFalse(PaypalApi::reversalRevokesEntitlement(
            'payment_reversed',
            12.50,
            12.50,
            7,
            7,
            '2026-09-17 00:00:00',
            '2026-08-17 00:00:00'
        ));
    }

    public function testSubscriptionDataUpdatesPreserveWebhookIdempotencyMetadata(): void
    {
        $existing = [
            'paymentmethod' => 'PaypalApi',
            'expected_amount' => '12.50',
            'processed_webhook_events' => ['WH-STATE-1'],
            'paypal' => ['id' => 'OLD'],
        ];
        $paypal = ['id' => 'NEW'];

        self::assertSame([
            'paymentmethod' => 'PaypalApi',
            'expected_amount' => '12.50',
            'processed_webhook_events' => ['WH-STATE-1'],
            'paypal' => $paypal,
        ], PaypalApi::updatedSubscriptionData($existing, $paypal));
    }

    public function testMissingLocalWebhookStateReturnsANonSuccessStatusForPaypalRetry(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php');

        self::assertIsString($source);
        preg_match_all(
            '/private static function handle(?:CompletedSubscriptionPayment|SubscriptionPaymentReversal|SubscriptionStatusEvent)\(.*?\n    }/s',
            $source,
            $matches
        );
        self::assertCount(3, $matches[0]);
        $handlers = implode("\n", $matches[0]);
        self::assertStringNotContainsString('202', $handlers);
        self::assertStringContainsString('409', $handlers);
    }

    public function testCompletedSaleDoesNotOverwriteTerminalSubscriptionState(): void
    {
        self::assertSame('Active', PaypalApi::statusAfterCompletedSale('Pending'));
        self::assertSame('Active', PaypalApi::statusAfterCompletedSale('Past Due'));
        self::assertSame('Canceled', PaypalApi::statusAfterCompletedSale('Canceled'));
        self::assertSame('Expired', PaypalApi::statusAfterCompletedSale('Expired'));
    }

    public function testRecurringCheckoutAndCallbackDoNotGrantBeforeACompletedSale(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php');

        self::assertIsString($source);
        $recurringStart = strpos($source, '} else {', strpos($source, "if(\$type == 'lifetime')"));
        $recurringEnd = strpos($source, 'header("Location: {$approvalUrl}");', $recurringStart);
        self::assertNotFalse($recurringStart);
        self::assertNotFalse($recurringEnd);
        $recurringCheckout = substr($source, $recurringStart, $recurringEnd - $recurringStart);
        self::assertStringNotContainsString('$user->pro = 1;', $recurringCheckout);
        self::assertStringNotContainsString('$user->planid = $plan->id;', $recurringCheckout);

        preg_match(
            '/private static function completeSubscription\(.*?\n    }\n\n    private static function handleWebhookEvent/s',
            $source,
            $matches
        );
        self::assertArrayHasKey(0, $matches);
        self::assertStringNotContainsString('DB::payment()->create()', $matches[0]);
        self::assertStringNotContainsString('$user->pro = 1;', $matches[0]);
        self::assertStringContainsString('updatedSubscriptionData($storedData, $response)', $matches[0]);
    }

    public function testVerifiedWebhookRoutesEverySubscriptionPaymentEventThroughOwnedHandlers(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/PaypalApi.php');

        self::assertIsString($source);
        self::assertStringContainsString("'BILLING.SUBSCRIPTION.PAYMENT.FAILED'", $source);
        self::assertStringContainsString('normalizeSubscriptionWebhookEvent($event)', $source);
        self::assertStringContainsString('handleCompletedSubscriptionPayment($action, $event)', $source);
        self::assertStringContainsString('handleSubscriptionPaymentReversal($action, $event)', $source);
        self::assertStringContainsString('handleSubscriptionStatusEvent($action, $event)', $source);
    }

    private function jsonResponse(array $payload, int $status = 200): array
    {
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload, JSON_THROW_ON_ERROR),
        ];
    }
}
}
