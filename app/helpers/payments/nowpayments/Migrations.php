<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;

final class Migrations
{
    /**
     * @return list<string>
     */
    public static function tables(): array
    {
        return [
            'nowpayments_transactions',
            'nowpayments_events',
            'nowpayments_plans',
            'nowpayments_customers',
            'nowpayments_outbox',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function uniqueKeys(): array
    {
        return [
            'nowpayments_transactions' => ['order_id', 'idempotency_key', 'provider_payment_id', 'provider_cycle_key'],
            'nowpayments_events' => ['payload_hash'],
            'nowpayments_plans' => ['mapping_key', 'remote_plan_id'],
            'nowpayments_customers' => ['userid', 'provider_subpartner_id', 'provider_name'],
            'nowpayments_outbox' => ['event_key', 'transaction_id'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function monetaryColumns(): array
    {
        return [
            'nowpayments_transactions' => [
                'expected_amount' => 'DECIMAL(36,18)',
                'pay_amount' => 'DECIMAL(36,18)',
                'received_amount' => 'DECIMAL(36,18)',
                'outcome_amount' => 'DECIMAL(36,18)',
            ],
            'nowpayments_plans' => [
                'amount' => 'DECIMAL(36,18)',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return [
            'enabled' => '0',
            'environment' => 'sandbox',
            'api_key_encrypted' => '',
            'ipn_secret_encrypted' => '',
            'dashboard_email' => '',
            'dashboard_password_encrypted' => '',
            'settlement_currency' => 'USD',
            'default_pay_currency' => '',
            'default_mode' => 'prepaid',
            'enabled_modes' => ['prepaid'],
            'custodial_enabled' => '0',
            'reconciliation_enabled' => '1',
            'reconciliation_interval_minutes' => 10,
        ];
    }

    public static function up(): void
    {
        self::createTransactions();
        self::createEvents();
        self::createPlans();
        self::createCustomers();
        self::createOutbox();
        self::upgradeExistingSchema();
    }

    public static function ensureSettings(): void
    {
        if (DB::settings()->where('config', 'nowpayments')->first()) {
            return;
        }

        $setting = DB::settings()->create();
        $setting->config = 'nowpayments';
        $setting->var = json_encode(self::defaultSettings(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $setting->save();
    }

    private static function createTransactions(): void
    {
        $table = self::table('nowpayments_transactions');
        DB::get_db()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` BIGINT NOT NULL AUTO_INCREMENT,
            `userid` BIGINT NULL,
            `planid` BIGINT NULL,
            `subscriptionid` BIGINT NULL,
            `paymentid` BIGINT NULL,
            `order_id` VARCHAR(191) NOT NULL,
            `idempotency_key` VARCHAR(191) NOT NULL,
            `provider_payment_id` VARCHAR(191) NULL,
            `provider_subscription_id` VARCHAR(191) NULL,
            `provider_cycle_key` VARCHAR(64) NULL,
            `mode` VARCHAR(32) NOT NULL,
            `term` VARCHAR(32) NOT NULL,
            `price_currency` VARCHAR(16) NOT NULL,
            `pay_currency` VARCHAR(32) NULL,
            `settlement_currency` VARCHAR(16) NULL,
            `expected_amount` DECIMAL(36,18) NOT NULL DEFAULT 0,
            `pay_amount` DECIMAL(36,18) NOT NULL DEFAULT 0,
            `received_amount` DECIMAL(36,18) NOT NULL DEFAULT 0,
            `outcome_amount` DECIMAL(36,18) NOT NULL DEFAULT 0,
            `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
            `provider_status` VARCHAR(64) NULL,
            `pay_address` TEXT NULL,
            `payin_extra_id` VARCHAR(191) NULL,
            `expires_at` DATETIME NULL,
            `last_checked_at` DATETIME NULL,
            `retry_count` INT NOT NULL DEFAULT 0,
            `next_retry_at` DATETIME NULL,
            `metadata` TEXT NULL,
            `entitlement_applied_at` DATETIME NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `np_transactions_order_id_unique` (`order_id`),
            UNIQUE KEY `np_transactions_idempotency_unique` (`idempotency_key`),
            UNIQUE KEY `np_transactions_payment_unique` (`provider_payment_id`),
            UNIQUE KEY `np_transactions_cycle_unique` (`provider_cycle_key`),
            KEY `np_transactions_subscription_index` (`provider_subscription_id`),
            KEY `np_transactions_user_index` (`userid`),
            KEY `np_transactions_plan_index` (`planid`),
            KEY `np_transactions_local_subscription_index` (`subscriptionid`),
            KEY `np_transactions_local_payment_index` (`paymentid`),
            KEY `np_transactions_mode_index` (`mode`),
            KEY `np_transactions_status_index` (`status`),
            KEY `np_transactions_retry_index` (`next_retry_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private static function createEvents(): void
    {
        DB::schema('nowpayments_events', static function ($table): void {
            $table->charset('utf8mb4');
            $table->increment('id');
            $table->bigint('transaction_id')->index();
            $table->string('provider_payment_id')->index();
            $table->string('payload_hash', 64, false)->unique();
            $table->int('signature_verified', 1, '0');
            $table->string('source', 32, 'ipn')->index();
            $table->string('status', 64);
            $table->string('result', 32, 'received');
            $table->text('failure_reason');
            $table->text('payload', false);
            $table->timestamp('received_at');
            $table->datetime('processed_at', null);
        });
    }

    private static function createPlans(): void
    {
        $table = self::table('nowpayments_plans');
        DB::get_db()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` BIGINT NOT NULL AUTO_INCREMENT,
            `mapping_key` VARCHAR(64) NOT NULL,
            `planid` BIGINT NULL,
            `term` VARCHAR(32) NOT NULL,
            `mode` VARCHAR(32) NOT NULL,
            `remote_plan_id` VARCHAR(191) NULL,
            `amount` DECIMAL(36,18) NOT NULL DEFAULT 0,
            `currency` VARCHAR(16) NOT NULL,
            `interval_days` INT NULL,
            `sync_hash` VARCHAR(64) NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `metadata` TEXT NULL,
            `last_synced_at` DATETIME NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `np_plans_mapping_unique` (`mapping_key`),
            UNIQUE KEY `np_plans_remote_unique` (`remote_plan_id`),
            KEY `np_plans_local_plan_index` (`planid`),
            KEY `np_plans_active_index` (`active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private static function createCustomers(): void
    {
        DB::schema('nowpayments_customers', static function ($table): void {
            $table->charset('utf8mb4');
            $table->increment('id');
            $table->bigint('userid', null, false)->unique();
            $table->string('provider_subpartner_id')->unique();
            $table->string('provider_name')->unique();
            $table->string('status', 32, 'pending')->index();
            $table->text('metadata');
            $table->datetime('last_balance_synced_at', null);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    private static function createOutbox(): void
    {
        $table = self::table('nowpayments_outbox');
        DB::get_db()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` BIGINT NOT NULL AUTO_INCREMENT,
            `event_key` VARCHAR(191) NOT NULL,
            `transaction_id` BIGINT NOT NULL,
            `userid` BIGINT NOT NULL,
            `planid` BIGINT NOT NULL,
            `paymentid` BIGINT NOT NULL,
            `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
            `attempts` INT NOT NULL DEFAULT 0,
            `last_error` VARCHAR(191) NULL,
            `available_at` DATETIME NOT NULL,
            `dispatched_at` DATETIME NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `np_outbox_event_unique` (`event_key`),
            UNIQUE KEY `np_outbox_transaction_unique` (`transaction_id`),
            KEY `np_outbox_pending_index` (`status`, `available_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private static function upgradeExistingSchema(): void
    {
        $events = self::table('nowpayments_events');
        $transactions = self::table('nowpayments_transactions');

        if (!self::columnExists($events, 'source')) {
            DB::get_db()->exec("ALTER TABLE `{$events}` ADD COLUMN `source` VARCHAR(32) NOT NULL DEFAULT 'ipn' AFTER `signature_verified`, ADD INDEX `np_events_source_index` (`source`)");
        }

        if (!self::columnExists($transactions, 'provider_cycle_key')) {
            DB::get_db()->exec("ALTER TABLE `{$transactions}` ADD COLUMN `provider_cycle_key` VARCHAR(64) NULL AFTER `provider_subscription_id`");
        }

        if (self::indexExists($transactions, 'np_transactions_subscription_unique')) {
            DB::get_db()->exec("ALTER TABLE `{$transactions}` DROP INDEX `np_transactions_subscription_unique`");
        }

        if (!self::indexExists($transactions, 'np_transactions_subscription_index')) {
            DB::get_db()->exec("ALTER TABLE `{$transactions}` ADD INDEX `np_transactions_subscription_index` (`provider_subscription_id`)");
        }

        if (!self::indexExists($transactions, 'np_transactions_cycle_unique')) {
            DB::get_db()->exec("ALTER TABLE `{$transactions}` ADD UNIQUE INDEX `np_transactions_cycle_unique` (`provider_cycle_key`)");
        }

        foreach (self::monetaryColumns() as $tableName => $columns) {
            $table = self::table($tableName);

            foreach ($columns as $column => $definition) {
                $metadata = self::columnMetadata($table, $column);

                if ($metadata === null
                    || strtolower((string) $metadata['DATA_TYPE']) !== 'decimal'
                    || (int) $metadata['NUMERIC_PRECISION'] !== 36
                    || (int) $metadata['NUMERIC_SCALE'] !== 18) {
                    DB::get_db()->exec("UPDATE `{$table}` SET `{$column}` = 0 WHERE `{$column}` IS NULL");
                    DB::get_db()->exec("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$definition} NOT NULL DEFAULT 0");
                }
            }
        }
    }

    private static function columnExists(string $table, string $column): bool
    {
        return self::columnMetadata($table, $column) !== null;
    }

    private static function indexExists(string $table, string $index): bool
    {
        $statement = DB::get_db()->prepare('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');
        $statement->execute([$table, $index]);

        return $statement->fetchColumn() !== false;
    }

    /** @return array<string, mixed>|null */
    private static function columnMetadata(string $table, string $column): ?array
    {
        $statement = DB::get_db()->prepare('SELECT DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $statement->execute([$table, $column]);
        $metadata = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($metadata) ? $metadata : null;
    }

    private static function table(string $name): string
    {
        $prefix = defined('DBprefix') ? (string) constant('DBprefix') : '';

        return str_replace('`', '``', $prefix.$name);
    }
}
