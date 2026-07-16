<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Core\DB;
use Core\Support\ORM;
use Helpers\Payments\Nowpayments\Client;
use Helpers\Payments\Nowpayments\Reconciler;
use Helpers\Payments\Nowpayments\Transport;
use Helpers\Payments\Nowpayments\TransportResponse;
use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 3);

foreach (['support/ORM.class.php', 'DB.class.php'] as $file) {
    require_once $root.'/core/'.$file;
}

foreach ([
    'Transport.php',
    'TransportResponse.php',
    'ApiException.php',
    'PrepaidApi.php',
    'Client.php',
    'Status.php',
    'Signature.php',
    'WebhookResult.php',
    'EntitlementService.php',
    'WebhookService.php',
    'Reconciler.php',
] as $file) {
    require_once $root.'/app/helpers/payments/nowpayments/'.$file;
}

defined('DBprefix') || define('DBprefix', '');

final class ReconcilerTest extends TestCase
{
    protected function setUp(): void
    {
        ORM::reset_db();
        ORM::reset_config();
        ORM::configure('sqlite::memory:');
        ORM::configure('return_result_sets', false);
        $this->createSchema(DB::get_db());
    }

    public function testRecurringPayloadDoesNotInventProviderAmountOrCurrency(): void
    {
        $transport = new ReconcilerTransport([
            new TransportResponse(200, [], '{"token":"jwt-token"}'),
            new TransportResponse(200, [], '{"result":{"id":"remote-sub-1","status":"PAID"}}'),
        ]);
        $reconciler = new Reconciler(
            new Client($transport, 'api-key', 'https://api.nowpayments.io/v1'),
            ['dashboard_email' => 'merchant@example.test', 'dashboard_password' => 'password']
        );
        $transaction = (object) [
            'provider_subscription_id' => 'remote-sub-1',
            'expected_amount' => '99.90',
            'price_currency' => 'USD',
        ];

        $method = (new \ReflectionClass($reconciler))->getMethod('recurringPayload');
        $payload = $method->invoke($reconciler, $transaction);

        self::assertSame('remote-sub-1', $payload['subscription_id']);
        self::assertSame('PAID', $payload['status']);
        self::assertArrayNotHasKey('price_amount', $payload);
        self::assertArrayNotHasKey('price_currency', $payload);
    }

    public function testUnknownPrepaidPaymentIsRecoveredByOrderIdAcrossRemotePages(): void
    {
        $firstPage = [];

        for ($index = 0; $index < 50; $index++) {
            $firstPage[] = [
                'payment_id' => 'other-'.$index,
                'order_id' => 'other-order-'.$index,
                'payment_status' => 'waiting',
            ];
        }

        $transport = new ReconcilerTransport([
            new TransportResponse(200, [], '{"token":"jwt-token"}'),
            new TransportResponse(200, [], json_encode(['data' => $firstPage], JSON_THROW_ON_ERROR)),
            new TransportResponse(200, [], json_encode(['data' => [[
                'payment_id' => 'provider-77',
                'order_id' => 'np-order-77',
                'payment_status' => 'provider_pending_review',
            ]]], JSON_THROW_ON_ERROR)),
        ]);
        $this->insertPendingPrepaid('np-order-77');
        $reconciler = new Reconciler(
            new Client($transport, 'api-key', 'https://api.nowpayments.io/v1'),
            ['dashboard_email' => 'merchant@example.test', 'dashboard_password' => 'password']
        );

        $reconciler->run(50);

        $transaction = DB::table('nowpayments_transactions')->where('order_id', 'np-order-77')->first();
        self::assertSame('provider-77', (string) $transaction->provider_payment_id);
        self::assertStringContainsString('page=0', $transport->requests[1]['url']);
        self::assertStringContainsString('page=1', $transport->requests[2]['url']);
    }

    public function testUnknownPrepaidRecoveryIsBoundedAndPersistsTheNextPage(): void
    {
        $page = [];

        for ($index = 0; $index < 50; $index++) {
            $page[] = [
                'payment_id' => 'other-'.$index,
                'order_id' => 'other-order-'.$index,
                'payment_status' => 'waiting',
            ];
        }

        $pageResponse = new TransportResponse(200, [], json_encode(['data' => $page], JSON_THROW_ON_ERROR));
        $transport = new ReconcilerTransport([
            new TransportResponse(200, [], '{"token":"jwt-token"}'),
            $pageResponse,
            $pageResponse,
            $pageResponse,
        ]);
        $this->insertPendingPrepaid('np-order-later');
        $reconciler = new Reconciler(
            new Client($transport, 'api-key', 'https://api.nowpayments.io/v1'),
            ['dashboard_email' => 'merchant@example.test', 'dashboard_password' => 'password']
        );

        $reconciler->run(50);

        $transaction = DB::table('nowpayments_transactions')->where('order_id', 'np-order-later')->first();
        $metadata = json_decode((string) $transaction->metadata, true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(4, $transport->requests);
        self::assertSame(3, $metadata['reconciliation_payment_page']);
    }

    public function testRecurringDiscoveryAdvancesRemoteOffsets(): void
    {
        $firstPage = [];

        for ($index = 0; $index < 50; $index++) {
            $firstPage[] = ['id' => 'unknown-'.$index, 'status' => 'WAITING_PAY', 'subscriber' => []];
        }

        $transport = new ReconcilerTransport([
            new TransportResponse(200, [], '{"token":"jwt-token"}'),
            new TransportResponse(200, [], json_encode(['result' => $firstPage], JSON_THROW_ON_ERROR)),
            new TransportResponse(200, [], json_encode(['result' => [[
                'id' => 'remote-recurring-51',
                'status' => 'WAITING_PAY',
                'subscriber' => ['email' => 'user@example.test'],
            ]]], JSON_THROW_ON_ERROR)),
        ]);
        $this->insertPlanMapping(1, 'remote-plan-1');
        DB::get_db()->exec("INSERT INTO user (id, email) VALUES (7, 'user@example.test')");
        DB::get_db()->exec("INSERT INTO subscription (id, userid, planid, status) VALUES (9, 7, 1, 'Pending')");
        $reconciler = new Reconciler(
            new Client($transport, 'api-key', 'https://api.nowpayments.io/v1'),
            ['dashboard_email' => 'merchant@example.test', 'dashboard_password' => 'password']
        );

        $method = (new \ReflectionClass($reconciler))->getMethod('discoverRecurring');
        $created = $method->invoke($reconciler, 100);

        self::assertSame(1, $created);
        self::assertStringContainsString('offset=0', $transport->requests[1]['url']);
        self::assertStringContainsString('offset=50', $transport->requests[2]['url']);
    }

    public function testRecurringDiscoveryPaginatesBeyondFirstFiftyPlanMappings(): void
    {
        $responses = [new TransportResponse(200, [], '{"token":"jwt-token"}')];

        for ($planId = 1; $planId <= 51; $planId++) {
            $this->insertPlanMapping($planId, 'remote-plan-'.$planId);
            $result = $planId === 51 ? [[
                'id' => 'remote-recurring-plan-51',
                'status' => 'WAITING_PAY',
                'subscriber' => ['email' => 'user@example.test'],
            ]] : [];
            $responses[] = new TransportResponse(200, [], json_encode(['result' => $result], JSON_THROW_ON_ERROR));
        }

        DB::get_db()->exec("INSERT INTO user (id, email) VALUES (7, 'user@example.test')");
        DB::get_db()->exec("INSERT INTO subscription (id, userid, planid, status) VALUES (9, 7, 51, 'Pending')");
        $transport = new ReconcilerTransport($responses);
        $reconciler = new Reconciler(
            new Client($transport, 'api-key', 'https://api.nowpayments.io/v1'),
            ['dashboard_email' => 'merchant@example.test', 'dashboard_password' => 'password']
        );

        $method = (new \ReflectionClass($reconciler))->getMethod('discoverRecurring');

        self::assertSame(1, $method->invoke($reconciler, 100));
        self::assertCount(52, $transport->requests);
    }

    public function testRecurringMappingCursorEventuallyReachesPlanBeyondBatchCap(): void
    {
        for ($planId = 1; $planId <= 101; $planId++) {
            $this->insertPlanMapping($planId, 'remote-plan-'.$planId);
        }

        DB::get_db()->exec("INSERT INTO user (id, email) VALUES (7, 'user@example.test')");
        DB::get_db()->exec("INSERT INTO subscription (id, userid, planid, status) VALUES (9, 7, 101, 'Pending')");
        $emptyResponses = [new TransportResponse(200, [], '{"token":"jwt-token"}')];

        for ($request = 0; $request < 100; $request++) {
            $emptyResponses[] = new TransportResponse(200, [], '{"result":[]}');
        }

        $first = new Reconciler(
            new Client(new ReconcilerTransport($emptyResponses), 'api-key', 'https://api.nowpayments.io/v1'),
            ['dashboard_email' => 'merchant@example.test', 'dashboard_password' => 'password']
        );
        $method = (new \ReflectionClass($first))->getMethod('discoverRecurring');
        self::assertSame(0, $method->invoke($first, 100));

        $secondResponses = [
            new TransportResponse(200, [], '{"token":"jwt-token"}'),
            new TransportResponse(200, [], json_encode(['result' => [[
                'id' => 'remote-recurring-plan-101',
                'status' => 'WAITING_PAY',
                'subscriber' => ['email' => 'user@example.test'],
            ]]], JSON_THROW_ON_ERROR)),
        ];

        for ($request = 1; $request < 100; $request++) {
            $secondResponses[] = new TransportResponse(200, [], '{"result":[]}');
        }

        $second = new Reconciler(
            new Client(new ReconcilerTransport($secondResponses), 'api-key', 'https://api.nowpayments.io/v1'),
            ['dashboard_email' => 'merchant@example.test', 'dashboard_password' => 'password']
        );
        $method = (new \ReflectionClass($second))->getMethod('discoverRecurring');

        self::assertSame(1, $method->invoke($second, 100));
    }

    public function testConcurrentRecurringDiscoveryUniqueRaceDoesNotAbortBatch(): void
    {
        $this->insertPlanMapping(1, 'remote-plan-race');
        DB::get_db()->exec("INSERT INTO user (id, email) VALUES (7, 'user@example.test')");
        DB::get_db()->exec("INSERT INTO subscription (id, userid, planid, status) VALUES (9, 7, 1, 'Pending')");
        DB::get_db()->exec("CREATE TRIGGER recurring_discovery_race
            AFTER INSERT ON nowpayments_transactions
            WHEN NEW.provider_subscription_id = 'remote-race'
            BEGIN
                SELECT RAISE(FAIL, 'UNIQUE constraint failed: nowpayments_transactions.provider_subscription_id');
            END");
        $transport = new ReconcilerTransport([
            new TransportResponse(200, [], '{"token":"jwt-token"}'),
            new TransportResponse(200, [], json_encode(['result' => [[
                'id' => 'remote-race',
                'status' => 'WAITING_PAY',
                'subscriber' => ['email' => 'user@example.test'],
            ]]], JSON_THROW_ON_ERROR)),
        ]);
        $reconciler = new Reconciler(
            new Client($transport, 'api-key', 'https://api.nowpayments.io/v1'),
            ['dashboard_email' => 'merchant@example.test', 'dashboard_password' => 'password']
        );
        $method = (new \ReflectionClass($reconciler))->getMethod('discoverRecurring');

        self::assertSame(0, $method->invoke($reconciler, 50));
        self::assertSame(
            1,
            DB::table('nowpayments_transactions')->where('provider_subscription_id', 'remote-race')->count()
        );
    }

    private function createSchema(\PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE nowpayments_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userid INTEGER,
            planid INTEGER,
            subscriptionid INTEGER,
            paymentid INTEGER,
            order_id TEXT UNIQUE,
            idempotency_key TEXT UNIQUE,
            provider_payment_id TEXT UNIQUE,
            provider_subscription_id TEXT UNIQUE,
            mode TEXT,
            term TEXT,
            price_currency TEXT,
            pay_currency TEXT,
            settlement_currency TEXT,
            expected_amount REAL,
            pay_amount REAL,
            received_amount REAL,
            outcome_amount REAL,
            status TEXT,
            provider_status TEXT,
            pay_address TEXT,
            payin_extra_id TEXT,
            expires_at TEXT,
            last_checked_at TEXT,
            retry_count INTEGER DEFAULT 0,
            next_retry_at TEXT,
            metadata TEXT,
            entitlement_applied_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )');
        $pdo->exec('CREATE TABLE nowpayments_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            mapping_key TEXT UNIQUE,
            planid INTEGER,
            term TEXT,
            mode TEXT,
            remote_plan_id TEXT UNIQUE,
            amount REAL,
            currency TEXT,
            interval_days INTEGER,
            sync_hash TEXT,
            active INTEGER,
            metadata TEXT,
            last_synced_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )');
        $pdo->exec('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT)');
        $pdo->exec('CREATE TABLE subscription (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userid INTEGER,
            planid INTEGER,
            status TEXT
        )');
    }

    private function insertPendingPrepaid(string $orderId): void
    {
        $statement = DB::get_db()->prepare('INSERT INTO nowpayments_transactions (
            userid, planid, subscriptionid, order_id, idempotency_key, mode, term,
            price_currency, pay_currency, expected_amount, status, retry_count,
            next_retry_at, metadata, created_at, updated_at
        ) VALUES (1, 1, 1, ?, ?, "prepaid", "monthly", "USD", "BTC", 10, "pending", 0,
            "2000-01-01 00:00:00", "{}", "2000-01-01 00:00:00", "2000-01-01 00:00:00")');
        $statement->execute([$orderId, hash('sha256', $orderId)]);
    }

    private function insertPlanMapping(int $planId, string $remotePlanId): void
    {
        $statement = DB::get_db()->prepare('INSERT INTO nowpayments_plans (
            mapping_key, planid, term, mode, remote_plan_id, amount, currency,
            interval_days, sync_hash, active, metadata, created_at, updated_at
        ) VALUES (?, ?, "monthly", "email", ?, 10, "USD", 30, ?, 1, "{}",
            "2000-01-01 00:00:00", "2000-01-01 00:00:00")');
        $statement->execute([
            hash('sha256', $remotePlanId),
            $planId,
            $remotePlanId,
            hash('sha256', 'sync-'.$remotePlanId),
        ]);
    }
}

final class ReconcilerTransport implements Transport
{
    public array $requests = [];

    public function __construct(private array $responses)
    {
    }

    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        array $options
    ): TransportResponse {
        $this->requests[] = compact('method', 'url', 'headers', 'body', 'options');

        return array_shift($this->responses) ?? new TransportResponse(500, [], '');
    }
}
