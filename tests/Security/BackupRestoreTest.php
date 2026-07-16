<?php

declare(strict_types=1);

namespace Tests\Security;

require_once dirname(__DIR__, 2).'/app/helpers/SafeBackup.php';

use Helpers\SafeBackup;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupRestoreTest extends TestCase
{
    private PDO $database;

    protected function setUp(): void
    {
        $this->database = new PDO('sqlite::memory:');
        $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->database->exec('PRAGMA foreign_keys = ON');
        $this->database->exec('CREATE TABLE user (id INTEGER PRIMARY KEY, email TEXT NOT NULL)');
        $this->database->exec('CREATE TABLE settings (id INTEGER PRIMARY KEY, config TEXT NOT NULL UNIQUE, var TEXT)');
    }

    public function testDecodesTheExistingLegacyGemFormat(): void
    {
        $backup = [
            'user' => [
                ['id' => '7', 'email' => 'owner@example.com'],
            ],
            'settings' => [],
        ];

        self::assertSame($backup, SafeBackup::decode(serialize($backup)));
    }

    public function testRejectsSerializedObjectsBeforeUnserializing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('objects');

        SafeBackup::decode(serialize(['user' => [['email' => new \stdClass()]]]));
    }

    public function testRejectsSerializedReferencesBeforeUnserializing(): void
    {
        $row = ['id' => '1'];
        $rows = [&$row, &$row];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('references');

        SafeBackup::decode(serialize(['user' => $rows]));
    }

    public function testRejectsUnknownTablesUnsafeColumnsAndNestedValues(): void
    {
        foreach ([
            ['unknown_table' => []],
            ['user' => [['email` = NULL; DROP TABLE user; --' => 'x']]],
            ['user' => [['email' => ['nested']]]],
        ] as $payload) {
            try {
                SafeBackup::decode(serialize($payload));
                self::fail('Unsafe backup data was accepted.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRejectsMalformedAndOversizedContent(): void
    {
        foreach ([
            'not serialized data',
            serialize('not an array'),
            serialize(['user' => []]).'trailing content',
            str_repeat('x', SafeBackup::MAX_BYTES + 1),
        ] as $payload) {
            try {
                SafeBackup::decode($payload);
                self::fail('Malformed or oversized backup content was accepted.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRestoresAllowedRowsWithBoundValues(): void
    {
        $this->database->exec("INSERT INTO user (id, email) VALUES (1, 'old@example.com')");

        SafeBackup::restore($this->database, [
            'user' => [
                ['id' => '2', 'email' => "new@example.com'); DROP TABLE settings; --"],
            ],
        ]);

        self::assertSame(
            [['id' => 2, 'email' => "new@example.com'); DROP TABLE settings; --"]],
            $this->database->query('SELECT id, email FROM user')->fetchAll(PDO::FETCH_ASSOC)
        );
        self::assertSame('settings', $this->database->query("SELECT name FROM sqlite_master WHERE name = 'settings'")->fetchColumn());
    }

    public function testRejectsColumnsThatDoNotExistWithoutMutatingData(): void
    {
        $this->database->exec("INSERT INTO user (id, email) VALUES (1, 'original@example.com')");

        try {
            SafeBackup::restore($this->database, [
                'user' => [['id' => '2', 'admin_backdoor' => '1']],
            ]);
            self::fail('Unknown database column was accepted.');
        } catch (InvalidArgumentException) {
            self::assertSame('original@example.com', $this->database->query('SELECT email FROM user')->fetchColumn());
        }
    }

    public function testRollsBackEveryTableWhenAnInsertFails(): void
    {
        $this->database->exec("INSERT INTO user (id, email) VALUES (1, 'original@example.com')");
        $this->database->exec("INSERT INTO settings (id, config, var) VALUES (1, 'title', 'Original')");

        try {
            SafeBackup::restore($this->database, [
                'user' => [['id' => '2', 'email' => 'replacement@example.com']],
                'settings' => [
                    ['id' => '2', 'config' => 'duplicate', 'var' => 'first'],
                    ['id' => '3', 'config' => 'duplicate', 'var' => 'second'],
                ],
            ]);
            self::fail('Expected the duplicate setting to fail.');
        } catch (RuntimeException) {
            self::assertSame(
                [['id' => 1, 'email' => 'original@example.com']],
                $this->database->query('SELECT id, email FROM user')->fetchAll(PDO::FETCH_ASSOC)
            );
            self::assertSame(
                [['id' => 1, 'config' => 'title', 'var' => 'Original']],
                $this->database->query('SELECT id, config, var FROM settings')->fetchAll(PDO::FETCH_ASSOC)
            );
        }
    }

    public function testNowPaymentsLedgerRoundTripPreservesBillingRelationshipsAndIdempotencyState(): void
    {
        $source = new PDO('sqlite::memory:');
        $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $source->exec('PRAGMA foreign_keys = ON');
        $source->exec('CREATE TABLE user (id INTEGER PRIMARY KEY, email TEXT NOT NULL)');
        $this->createBillingLedgerSchema($source);
        $this->createBillingLedgerSchema($this->database);

        $source->exec("INSERT INTO plans (id, name) VALUES (31, 'Pro')");
        $source->exec("INSERT INTO user (id, email) VALUES (41, 'billing@example.com')");
        $source->exec("INSERT INTO subscription (id, userid, planid, status) VALUES (51, 41, 31, 'Active')");
        $source->exec("INSERT INTO payment (id, userid, subscriptionid, tid) VALUES (61, 41, 51, 'provider-payment-71')");
        $source->exec("INSERT INTO nowpayments_plans (id, planid, mapping_key, remote_plan_id) VALUES (81, 31, 'pro:monthly:email', 'remote-plan-81')");
        $source->exec("INSERT INTO nowpayments_customers (id, userid, provider_subpartner_id, provider_name) VALUES (91, 41, 'subpartner-91', 'customer-41')");
        $source->exec("INSERT INTO nowpayments_transactions (id, userid, planid, subscriptionid, paymentid, order_id, idempotency_key, provider_payment_id, status, retry_count, entitlement_applied_at) VALUES (101, 41, 31, 51, 61, 'order-101', 'idem-101', 'provider-payment-71', 'finished', 2, '2026-07-16 12:00:00')");
        $source->exec("INSERT INTO nowpayments_events (id, transaction_id, payload_hash, result, processed_at) VALUES (111, 101, 'payload-hash-111', 'processed', '2026-07-16 12:01:00')");

        $this->database->exec("INSERT INTO plans (id, name) VALUES (1, 'Old')");
        $this->database->exec("INSERT INTO user (id, email) VALUES (1, 'old@example.com')");
        $this->database->exec("INSERT INTO subscription (id, userid, planid, status) VALUES (1, 1, 1, 'Expired')");
        $this->database->exec("INSERT INTO payment (id, userid, subscriptionid, tid) VALUES (1, 1, 1, 'old-payment')");
        $this->database->exec("INSERT INTO nowpayments_plans (id, planid, mapping_key, remote_plan_id) VALUES (1, 1, 'old:monthly:email', 'old-plan')");
        $this->database->exec("INSERT INTO nowpayments_customers (id, userid, provider_subpartner_id, provider_name) VALUES (1, 1, 'old-subpartner', 'old-customer')");
        $this->database->exec("INSERT INTO nowpayments_transactions (id, userid, planid, subscriptionid, paymentid, order_id, idempotency_key, provider_payment_id, status, retry_count, entitlement_applied_at) VALUES (1, 1, 1, 1, 1, 'old-order', 'old-idem', 'old-payment', 'pending', 0, NULL)");
        $this->database->exec("INSERT INTO nowpayments_events (id, transaction_id, payload_hash, result, processed_at) VALUES (1, 1, 'old-hash', 'received', NULL)");

        $tables = [
            'plans',
            'user',
            'subscription',
            'payment',
            'nowpayments_plans',
            'nowpayments_customers',
            'nowpayments_transactions',
            'nowpayments_events',
        ];
        $fixture = [];

        foreach ($tables as $table) {
            $fixture[$table] = $source->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC);
        }

        $decoded = SafeBackup::decode(serialize($fixture));

        self::assertSame(8, SafeBackup::restore($this->database, $decoded));
        self::assertSame(
            [[
                'order_id' => 'order-101',
                'idempotency_key' => 'idem-101',
                'provider_payment_id' => 'provider-payment-71',
                'status' => 'finished',
                'retry_count' => 2,
                'entitlement_applied_at' => '2026-07-16 12:00:00',
                'email' => 'billing@example.com',
                'plan_name' => 'Pro',
                'subscription_status' => 'Active',
                'payment_tid' => 'provider-payment-71',
                'remote_plan_id' => 'remote-plan-81',
                'provider_subpartner_id' => 'subpartner-91',
            ]],
            $this->database->query(
                'SELECT t.order_id, t.idempotency_key, t.provider_payment_id, t.status, t.retry_count, '
                .'t.entitlement_applied_at, u.email, p.name AS plan_name, s.status AS subscription_status, '
                .'pay.tid AS payment_tid, np.remote_plan_id, nc.provider_subpartner_id '
                .'FROM nowpayments_transactions t '
                .'JOIN user u ON u.id = t.userid '
                .'JOIN plans p ON p.id = t.planid '
                .'JOIN subscription s ON s.id = t.subscriptionid '
                .'JOIN payment pay ON pay.id = t.paymentid '
                .'JOIN nowpayments_plans np ON np.planid = t.planid '
                .'JOIN nowpayments_customers nc ON nc.userid = t.userid'
            )->fetchAll(PDO::FETCH_ASSOC)
        );
        self::assertSame(
            [['transaction_id' => 101, 'payload_hash' => 'payload-hash-111', 'result' => 'processed', 'processed_at' => '2026-07-16 12:01:00']],
            $this->database->query('SELECT transaction_id, payload_hash, result, processed_at FROM nowpayments_events')->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function testControllerExportsNowPaymentsLedgerInDependencySafeOrder(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/controllers/admin/DashboardController.php');

        self::assertIsString($source);

        $lastPosition = -1;

        foreach ([
            'nowpayments_plans',
            'nowpayments_customers',
            'nowpayments_transactions',
            'nowpayments_events',
        ] as $table) {
            $statement = "\$data['{$table}'] = DB::table('{$table}')->findArray();";
            $position = strpos($source, $statement);

            self::assertNotFalse($position, 'Missing backup export for '.$table.'.');
            self::assertGreaterThan($lastPosition, $position, $table.' is not exported in dependency-safe order.');
            $lastPosition = $position;
        }
    }

    public function testControllerUsesTheSafeTransactionalRestoreBoundary(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/controllers/admin/DashboardController.php');

        self::assertIsString($source);
        self::assertStringContainsString('SafeBackup::read', $source);
        self::assertStringContainsString('SafeBackup::restore', $source);
        self::assertStringNotContainsString('unserialize(file_get_contents($file->location))', $source);
        self::assertStringNotContainsString('DB::truncate($table)', $source);
    }

    private function createBillingLedgerSchema(PDO $database): void
    {
        $database->exec('CREATE TABLE plans (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        $database->exec('CREATE TABLE subscription (id INTEGER PRIMARY KEY, userid INTEGER NOT NULL REFERENCES user(id), planid INTEGER NOT NULL REFERENCES plans(id), status TEXT NOT NULL)');
        $database->exec('CREATE TABLE payment (id INTEGER PRIMARY KEY, userid INTEGER NOT NULL REFERENCES user(id), subscriptionid INTEGER NOT NULL REFERENCES subscription(id), tid TEXT NOT NULL UNIQUE)');
        $database->exec('CREATE TABLE nowpayments_plans (id INTEGER PRIMARY KEY, planid INTEGER NOT NULL REFERENCES plans(id), mapping_key TEXT NOT NULL UNIQUE, remote_plan_id TEXT NOT NULL UNIQUE)');
        $database->exec('CREATE TABLE nowpayments_customers (id INTEGER PRIMARY KEY, userid INTEGER NOT NULL UNIQUE REFERENCES user(id), provider_subpartner_id TEXT NOT NULL UNIQUE, provider_name TEXT NOT NULL UNIQUE)');
        $database->exec('CREATE TABLE nowpayments_transactions (id INTEGER PRIMARY KEY, userid INTEGER NOT NULL REFERENCES user(id), planid INTEGER NOT NULL REFERENCES plans(id), subscriptionid INTEGER NOT NULL REFERENCES subscription(id), paymentid INTEGER NOT NULL REFERENCES payment(id), order_id TEXT NOT NULL UNIQUE, idempotency_key TEXT NOT NULL UNIQUE, provider_payment_id TEXT NOT NULL UNIQUE, status TEXT NOT NULL, retry_count INTEGER NOT NULL, entitlement_applied_at TEXT)');
        $database->exec('CREATE TABLE nowpayments_events (id INTEGER PRIMARY KEY, transaction_id INTEGER NOT NULL REFERENCES nowpayments_transactions(id), payload_hash TEXT NOT NULL UNIQUE, result TEXT NOT NULL, processed_at TEXT)');
    }
}
