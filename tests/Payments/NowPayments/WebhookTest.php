<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Core\DB;
use Core\Plugin;
use Helpers\Payments\Nowpayments\EntitlementService;
use Helpers\Payments\Nowpayments\Signature;
use Helpers\Payments\Nowpayments\Status;
use Helpers\Payments\Nowpayments\WebhookService;
use PDO;
use PHPUnit\Framework\TestCase;

if (!defined('DBprefix')) {
    define('DBprefix', '');
}

$project = dirname(__DIR__, 3);
require_once $project.'/core/support/ORM.class.php';
require_once $project.'/core/DB.class.php';
require_once $project.'/core/Plugin.class.php';

foreach (['Signature', 'Status', 'WebhookResult', 'RecurringCycle', 'EntitlementService', 'WebhookService'] as $classFile) {
    require_once $project.'/app/helpers/payments/nowpayments/'.$classFile.'.php';
}

final class WebhookTest extends TestCase
{
    private const SECRET = 'ipn-secret';

    private PDO $database;
    private static int $dispatchCount = 0;
    private static int $dispatchFailures = 0;
    /** @var list<string|null> */
    private static array $dispatchKeys = [];

    public static function setUpBeforeClass(): void
    {
        Plugin::register('payment.success', static function (array $data): void {
            if (self::$dispatchFailures > 0) {
                self::$dispatchFailures--;
                throw new \RuntimeException('simulated listener failure');
            }

            self::$dispatchCount++;
            self::$dispatchKeys[] = $data[3] ?? null;
        });
    }

    protected function setUp(): void
    {
        self::$dispatchCount = 0;
        self::$dispatchFailures = 0;
        self::$dispatchKeys = [];
        $this->database = new PDO('sqlite::memory:');
        $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        DB::set_db($this->database);
        $this->createSchema();
    }

    public function testOfficialStandardIpnAppliesAndDispatchesExactlyOnce(): void
    {
        $this->seedTransaction(providerPaymentId: '6249365965');
        $payload = $this->standardPayload();
        $service = new WebhookService(new EntitlementService());

        self::assertSame('processed', $service->handle($payload, $this->signature($payload), self::SECRET)->result);
        self::assertSame('duplicate', $service->handle($payload, $this->signature($payload), self::SECRET)->result);
        self::assertSame(1, self::$dispatchCount);
        self::assertSame(['payment.success:1'], self::$dispatchKeys);
        self::assertSame(1, (int) $this->database->query('SELECT COUNT(*) FROM payment')->fetchColumn());
        self::assertSame('dispatched', $this->database->query('SELECT status FROM nowpayments_outbox')->fetchColumn());
    }

    public function testOfficialCustodialRecurringIpnShapeIsAccepted(): void
    {
        $amount = '12.171365564140688';
        $this->seedTransaction(
            mode: 'custodial',
            expectedAmount: $amount,
            currency: 'TRX',
            providerSubscriptionId: '1234567890'
        );
        $payload = [
            'id' => '1234567890',
            'status' => 'FINISHED',
            'currency' => 'trx',
            'amount' => $amount,
            'ipn_callback_url' => 'https://example.test/webhook/nowpayments',
            'created_at' => '2023-07-26T14:20:11.531Z',
            'updated_at' => '2023-07-26T14:20:21.079Z',
        ];

        $result = (new WebhookService())->handle($payload, $this->signature($payload), self::SECRET);

        self::assertSame(200, $result->httpStatus);
        self::assertSame('processed', $result->result);
        self::assertSame(1, self::$dispatchCount);
        self::assertSame($amount, $this->database->query('SELECT amount FROM payment')->fetchColumn());
    }

    public function testFailedOutboxDeliveryRemainsImmediatelyRetryable(): void
    {
        $this->seedTransaction(providerPaymentId: '6249365965');
        $payload = $this->standardPayload();
        $service = new WebhookService();
        self::$dispatchFailures = 1;

        try {
            $service->handle($payload, $this->signature($payload), self::SECRET);
            self::fail('Expected the simulated listener failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('simulated listener failure', $exception->getMessage());
        }

        self::assertSame('duplicate', $service->handle($payload, $this->signature($payload), self::SECRET)->result);
        self::assertSame(1, self::$dispatchCount);
        self::assertSame('dispatched', $this->database->query('SELECT status FROM nowpayments_outbox')->fetchColumn());
    }

    public function testPaidIpnWithoutIndependentMonetaryContextIsRejected(): void
    {
        $this->seedTransaction(providerPaymentId: '6249365965');
        $payload = ['payment_id' => '6249365965', 'payment_status' => 'finished'];

        $result = (new WebhookService())->handle($payload, $this->signature($payload), self::SECRET);

        self::assertSame(422, $result->httpStatus);
        self::assertSame('insufficient_provider_context', $result->result);
        self::assertSame(0, self::$dispatchCount);
        self::assertSame(0, (int) $this->database->query('SELECT COUNT(*) FROM payment')->fetchColumn());
    }

    public function testDecimalMismatchIsRejectedWithoutTolerance(): void
    {
        $this->seedTransaction(providerPaymentId: '6249365965');
        $payload = $this->standardPayload();
        $payload['price_amount'] = '10.000000000000000001';

        $result = (new WebhookService())->handle($payload, $this->signature($payload), self::SECRET);

        self::assertSame(422, $result->httpStatus);
        self::assertSame('context_mismatch', $result->result);
        self::assertSame(0, self::$dispatchCount);
    }

    public function testSignedAndReconciledEventsKeepDistinctTrustProvenance(): void
    {
        $this->seedTransaction(providerPaymentId: '6249365965');
        $payload = $this->standardPayload();
        $service = new WebhookService();

        self::assertSame('processed', $service->handle($payload, $this->signature($payload), self::SECRET)->result);
        self::assertSame('processed', $service->handleTrusted($payload)->result);

        $events = $this->database->query('SELECT source, signature_verified FROM nowpayments_events ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        self::assertSame([
            ['source' => 'ipn', 'signature_verified' => 1],
            ['source' => 'reconciliation', 'signature_verified' => 0],
        ], $events);
        self::assertSame(1, self::$dispatchCount);
    }

    public function testReconciliationCannotSupplyFabricatedRecurringValidationValues(): void
    {
        $this->seedTransaction(
            mode: 'custodial',
            expectedAmount: '12.171365564140688',
            currency: 'TRX',
            providerSubscriptionId: '1234567890'
        );

        $result = (new WebhookService())->handleTrusted([
            'subscription_id' => '1234567890',
            'status' => 'PAID',
            'price_amount' => '12.171365564140688',
            'price_currency' => 'TRX',
            'updated_at' => '2023-07-26T14:20:21.079Z',
        ]);

        self::assertSame(422, $result->httpStatus);
        self::assertSame('insufficient_provider_context', $result->result);
        self::assertSame(0, self::$dispatchCount);
    }

    public function testRecurringReconciliationAcceptsMappedContextWithoutAmountFields(): void
    {
        $cycleKey = hash('sha256', 'subscription:renewal-1|period:2026-08-17 00:00:00');
        $this->seedTransaction(
            mode: 'email',
            expectedAmount: '10.10',
            currency: 'USD',
            providerSubscriptionId: 'renewal-1',
            providerCycleKey: $cycleKey,
            metadata: '{"remote_plan_id":"remote-plan-1","subscriber_email":"user@example.test"}'
        );
        $this->database->exec("UPDATE user SET email = 'user@example.test' WHERE id = 1");
        $payload = [
            'id' => 'renewal-1',
            'status' => 'PAID',
            'subscription_plan_id' => 'remote-plan-1',
            'subscriber' => ['email' => 'user@example.test'],
            'expire_date' => '2026-08-17T00:00:00Z',
        ];

        $result = (new WebhookService())->handleTrusted($payload);

        self::assertSame(200, $result->httpStatus);
        self::assertSame('processed', $result->result);
        self::assertSame(1, (int) $this->database->query('SELECT COUNT(*) FROM payment')->fetchColumn());
    }

    public function testActiveRecurringSubscriptionAmountRemainsCumulative(): void
    {
        $this->seedTransaction(
            mode: 'custodial',
            expectedAmount: '10.10',
            currency: 'USD',
            providerSubscriptionId: 'renewal-2',
            subscriptionStatus: 'Active',
            subscriptionAmount: '20.15'
        );
        $payload = [
            'id' => 'renewal-2',
            'status' => 'FINISHED',
            'currency' => 'USD',
            'amount' => '10.10',
            'expire_date' => '2026-08-17T00:00:00Z',
        ];

        self::assertSame('processed', (new WebhookService())->handle($payload, $this->signature($payload), self::SECRET)->result);
        self::assertSame('30.25', $this->database->query('SELECT amount FROM subscription')->fetchColumn());
    }

    public function testLaterRecurringCycleCreatesASeparatePaymentAndExtendsEntitlement(): void
    {
        $this->seedTransaction(
            mode: 'email',
            expectedAmount: '10.10',
            currency: 'USD',
            providerSubscriptionId: 'renewing-subscription',
            subscriptionAmount: '0'
        );
        $service = new WebhookService();
        $first = [
            'id' => 'renewing-subscription',
            'status' => 'FINISHED',
            'currency' => 'USD',
            'amount' => '10.10',
            'expire_date' => '2026-08-17T00:00:00Z',
        ];
        $second = $first;
        $second['expire_date'] = '2026-09-17T00:00:00Z';

        self::assertSame('processed', $service->handle($first, $this->signature($first), self::SECRET)->result);
        $firstExpiry = (string) $this->database->query('SELECT expiry FROM subscription')->fetchColumn();
        self::assertSame('processed', $service->handle($second, $this->signature($second), self::SECRET)->result);

        self::assertSame(2, (int) $this->database->query('SELECT COUNT(*) FROM nowpayments_transactions')->fetchColumn());
        self::assertSame(2, (int) $this->database->query('SELECT COUNT(*) FROM payment')->fetchColumn());
        self::assertSame(2, (int) $this->database->query('SELECT COUNT(DISTINCT provider_cycle_key) FROM nowpayments_transactions')->fetchColumn());
        self::assertSame('20.2', $this->database->query('SELECT amount FROM subscription')->fetchColumn());
        self::assertGreaterThan($firstExpiry, (string) $this->database->query('SELECT expiry FROM subscription')->fetchColumn());
    }

    /** @return array<string, mixed> */
    private function standardPayload(): array
    {
        return [
            'payment_id' => '6249365965',
            'payment_status' => 'finished',
            'price_amount' => '10.00',
            'price_currency' => 'usd',
            'pay_amount' => '11.8',
            'actually_paid' => '12',
            'pay_currency' => 'trx',
            'order_id' => 'np-order-1',
            'purchase_id' => '5312822613',
            'outcome_amount' => '11.8405',
            'outcome_currency' => 'trx',
        ];
    }

    private function signature(array $payload): string
    {
        return hash_hmac('sha512', Signature::canonicalJson($payload), self::SECRET);
    }

    private function seedTransaction(
        string $mode = 'prepaid',
        string $expectedAmount = '10.00',
        string $currency = 'USD',
        ?string $providerPaymentId = null,
        ?string $providerSubscriptionId = null,
        ?string $providerCycleKey = null,
        string $subscriptionStatus = 'Pending',
        ?string $subscriptionAmount = null,
        string $metadata = '{}'
    ): void {
        $this->database->exec("INSERT INTO user (expiration, trial, pro, planid) VALUES (NULL, 1, 0, 0)");
        $this->database->prepare('INSERT INTO subscription (userid, plan, planid, status, amount, data, uniqueid) VALUES (1, ?, 7, ?, ?, ?, ?)')->execute([
            'monthly',
            $subscriptionStatus,
            $subscriptionAmount ?? $expectedAmount,
            '{}',
            'np-order-1',
        ]);
        $this->database->prepare('INSERT INTO nowpayments_transactions (
            userid, planid, subscriptionid, paymentid, order_id, idempotency_key,
            provider_payment_id, provider_subscription_id, provider_cycle_key, mode, term, price_currency,
            settlement_currency, expected_amount, received_amount, outcome_amount, status,
            retry_count, metadata, created_at, updated_at
        ) VALUES (1, 7, 1, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, 0, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)')->execute([
            'np-order-1',
            'idempotency-1',
            $providerPaymentId,
            $providerSubscriptionId,
            $providerCycleKey,
            $mode,
            'monthly',
            strtoupper($currency),
            strtoupper($currency),
            $expectedAmount,
            Status::PENDING,
            $metadata,
        ]);
    }

    private function createSchema(): void
    {
        foreach ([
            'CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, expiration TEXT, last_payment TEXT, trial INTEGER, pro INTEGER, planid INTEGER)',
            'CREATE TABLE subscription (id INTEGER PRIMARY KEY AUTOINCREMENT, tid TEXT, userid INTEGER, plan TEXT, planid INTEGER, status TEXT, amount TEXT, expiry TEXT, lastpayment TEXT, data TEXT, uniqueid TEXT)',
            'CREATE TABLE payment (id INTEGER PRIMARY KEY AUTOINCREMENT, date TEXT, tid TEXT UNIQUE, amount TEXT, userid INTEGER, status TEXT, expiry TEXT, data TEXT)',
            'CREATE TABLE coupons (id INTEGER PRIMARY KEY AUTOINCREMENT, used INTEGER)',
            'CREATE TABLE nowpayments_transactions (id INTEGER PRIMARY KEY AUTOINCREMENT, userid INTEGER, planid INTEGER, subscriptionid INTEGER, paymentid INTEGER, order_id TEXT UNIQUE, idempotency_key TEXT UNIQUE, provider_payment_id TEXT UNIQUE, provider_subscription_id TEXT, provider_cycle_key TEXT UNIQUE, mode TEXT, term TEXT, price_currency TEXT, pay_currency TEXT, settlement_currency TEXT, expected_amount TEXT, pay_amount TEXT, received_amount TEXT, outcome_amount TEXT, status TEXT, provider_status TEXT, pay_address TEXT, payin_extra_id TEXT, expires_at TEXT, last_checked_at TEXT, retry_count INTEGER, next_retry_at TEXT, metadata TEXT, entitlement_applied_at TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE nowpayments_events (id INTEGER PRIMARY KEY AUTOINCREMENT, transaction_id INTEGER, provider_payment_id TEXT, payload_hash TEXT UNIQUE, signature_verified INTEGER, source TEXT, status TEXT, result TEXT, failure_reason TEXT, payload TEXT, received_at TEXT, processed_at TEXT)',
            'CREATE TABLE nowpayments_outbox (id INTEGER PRIMARY KEY AUTOINCREMENT, event_key TEXT UNIQUE, transaction_id INTEGER UNIQUE, userid INTEGER, planid INTEGER, paymentid INTEGER, status TEXT, attempts INTEGER, last_error TEXT, available_at TEXT, dispatched_at TEXT, created_at TEXT, updated_at TEXT)',
        ] as $sql) {
            $this->database->exec($sql);
        }
    }
}
