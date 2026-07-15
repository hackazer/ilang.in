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
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function uniqueKeys(): array
    {
        return [
            'nowpayments_transactions' => ['order_id', 'idempotency_key', 'provider_payment_id'],
            'nowpayments_events' => ['payload_hash'],
            'nowpayments_plans' => ['mapping_key', 'remote_plan_id'],
            'nowpayments_customers' => ['userid', 'provider_subpartner_id', 'provider_name'],
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
            'api_key' => '',
            'ipn_secret' => '',
            'dashboard_email' => '',
            'dashboard_password' => '',
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
        DB::schema('nowpayments_transactions', static function ($table): void {
            $table->charset('utf8mb4');
            $table->increment('id');
            $table->bigint('userid')->index();
            $table->bigint('planid')->index();
            $table->bigint('subscriptionid')->index();
            $table->bigint('paymentid')->index();
            $table->string('order_id', 191, false)->unique();
            $table->string('idempotency_key', 191, false)->unique();
            $table->string('provider_payment_id')->unique();
            $table->string('provider_subscription_id')->index();
            $table->string('mode', 32, false)->index();
            $table->string('term', 32, false);
            $table->string('price_currency', 16, false);
            $table->string('pay_currency', 32);
            $table->string('settlement_currency', 16);
            $table->double('expected_amount', '20,8', '0');
            $table->double('received_amount', '20,8', '0');
            $table->double('outcome_amount', '20,8', '0');
            $table->string('status', 32, 'pending')->index();
            $table->string('provider_status', 64);
            $table->text('pay_address');
            $table->string('payin_extra_id');
            $table->datetime('expires_at', null);
            $table->datetime('last_checked_at', null);
            $table->integer('retry_count', null, '0');
            $table->datetime('next_retry_at', null)->index();
            $table->text('metadata');
            $table->datetime('entitlement_applied_at', null);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
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
        DB::schema('nowpayments_plans', static function ($table): void {
            $table->charset('utf8mb4');
            $table->increment('id');
            $table->string('mapping_key', 64, false)->unique();
            $table->bigint('planid')->index();
            $table->string('term', 32, false);
            $table->string('mode', 32, false);
            $table->string('remote_plan_id')->unique();
            $table->double('amount', '20,8', '0');
            $table->string('currency', 16, false);
            $table->integer('interval_days');
            $table->string('sync_hash', 64);
            $table->int('active', 1, '1')->index();
            $table->text('metadata');
            $table->datetime('last_synced_at', null);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
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
}
