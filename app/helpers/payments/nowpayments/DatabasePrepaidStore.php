<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class DatabasePrepaidStore implements PrepaidStore
{
    public function findByIdempotencyKey(string $key): ?PrepaidAttempt
    {
        $transaction = DB::table('nowpayments_transactions')->where('idempotency_key', $key)->first();

        return $transaction ? self::attempt($transaction) : null;
    }

    public function createPending(PrepaidCommand $command): PrepaidAttempt
    {
        $pdo = DB::get_db();
        $pdo->beginTransaction();

        try {
            $subscription = DB::subscription()->create();
            $subscription->tid = null;
            $subscription->userid = $command->userId;
            $subscription->plan = $command->term;
            $subscription->planid = $command->planId;
            $subscription->status = 'Pending';
            $subscription->amount = $command->amount;
            $subscription->date = Helper::dtime();
            $subscription->expiry = Helper::dtime();
            $subscription->lastpayment = Helper::dtime();
            $subscription->data = json_encode(['type' => 'nowpayments', 'paymentmethod' => 'nowpayments'], JSON_THROW_ON_ERROR);
            $subscription->uniqueid = $command->orderId;
            $subscription->save();

            $transaction = DB::table('nowpayments_transactions')->create();
            $transaction->userid = $command->userId;
            $transaction->planid = $command->planId;
            $transaction->subscriptionid = $subscription->id();
            $transaction->order_id = $command->orderId;
            $transaction->idempotency_key = $command->idempotencyKey;
            $transaction->mode = 'prepaid';
            $transaction->term = $command->term;
            $transaction->price_currency = strtoupper($command->priceCurrency);
            $transaction->pay_currency = strtoupper($command->payCurrency);
            $transaction->settlement_currency = strtoupper($command->priceCurrency);
            $transaction->expected_amount = $command->amount;
            $transaction->received_amount = '0';
            $transaction->outcome_amount = '0';
            $transaction->status = Status::PENDING;
            $transaction->retry_count = 0;
            $transaction->next_retry_at = Helper::dtime('+2 minutes');
            $transaction->metadata = json_encode($command->metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $transaction->created_at = Helper::dtime();
            $transaction->updated_at = Helper::dtime();
            $transaction->save();

            $pdo->commit();

            return self::attempt($transaction);
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($existing = $this->findByIdempotencyKey($command->idempotencyKey)) {
                return $existing;
            }

            throw $exception;
        }
    }

    public function markCreated(PrepaidAttempt $attempt, array $response): PrepaidAttempt
    {
        $transaction = DB::table('nowpayments_transactions')->where('id', $attempt->transactionId())->first();

        if (!$transaction) {
            throw new \RuntimeException('NOWPayments transaction disappeared before provider confirmation.');
        }

        $transaction->provider_payment_id = (string) $response['payment_id'];
        $transaction->provider_status = (string) ($response['payment_status'] ?? 'waiting');
        $transaction->status = Status::normalize($transaction->provider_status);
        $transaction->pay_currency = strtoupper((string) ($response['pay_currency'] ?? $transaction->pay_currency));
        $transaction->pay_amount = self::decimal($response['pay_amount'] ?? 0);
        $transaction->pay_address = (string) ($response['pay_address'] ?? '');
        $transaction->payin_extra_id = (string) ($response['payin_extra_id'] ?? '');
        $transaction->expires_at = self::providerDate($response);
        $transaction->metadata = json_encode(
            array_replace(self::metadata($transaction), ['provider' => self::sanitizedResponse($response)]),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
        );
        $transaction->updated_at = Helper::dtime();
        $transaction->save();

        return self::attempt($transaction, $response);
    }

    public function markFailed(PrepaidAttempt $attempt): void
    {
        if ($transaction = DB::table('nowpayments_transactions')->where('id', $attempt->transactionId())->first()) {
            $transaction->status = Status::FAILED;
            $transaction->provider_status = 'request_failed';
            $transaction->next_retry_at = null;
            $transaction->updated_at = Helper::dtime();
            $transaction->save();
        }

        if ($subscription = DB::subscription()->where('id', $attempt->subscriptionId())->first()) {
            $subscription->status = 'Failed';
            $subscription->save();
        }
    }

    private static function attempt(object $transaction, array $response = []): PrepaidAttempt
    {
        return new PrepaidAttempt(
            (int) $transaction->id,
            (int) $transaction->subscriptionid,
            (string) $transaction->order_id,
            (string) $transaction->idempotency_key,
            (string) $transaction->status,
            $transaction->provider_payment_id ? (string) $transaction->provider_payment_id : null,
            $response
        );
    }

    /** @return array<string, mixed> */
    private static function metadata(object $transaction): array
    {
        $metadata = json_decode((string) $transaction->metadata, true);

        return is_array($metadata) ? $metadata : [];
    }

    /** @return array<string, mixed> */
    private static function sanitizedResponse(array $response): array
    {
        return array_intersect_key($response, array_flip([
            'payment_id', 'payment_status', 'pay_address', 'payin_extra_id', 'price_amount',
            'price_currency', 'pay_amount', 'pay_currency', 'purchase_id', 'amount_received',
            'outcome_amount', 'outcome_currency', 'expiration_estimate_date', 'valid_until',
        ]));
    }

    private static function providerDate(array $response): ?string
    {
        $value = $response['expiration_estimate_date'] ?? $response['valid_until'] ?? null;

        if (!is_string($value) || strtotime($value) === false) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime($value));
    }

    private static function decimal(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value, 8, '.', '') : '0.00000000';
    }
}
