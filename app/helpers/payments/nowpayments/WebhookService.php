<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class WebhookService
{
    public function __construct(private readonly EntitlementService $entitlements = new EntitlementService())
    {
    }

    public function handle(array $payload, string $providedSignature, string $secret): WebhookResult
    {
        if (!Signature::verify($payload, $providedSignature, $secret)) {
            return new WebhookResult(401, 'invalid_signature');
        }

        return $this->process($payload);
    }

    public function handleTrusted(array $payload): WebhookResult
    {
        return $this->process($payload);
    }

    private function process(array $payload): WebhookResult
    {

        $providerId = trim((string) ($payload['payment_id'] ?? ''));
        $subscriptionId = trim((string) ($payload['subscription_id'] ?? ''));
        $orderId = trim((string) ($payload['order_id'] ?? ''));
        $providerStatus = trim((string) ($payload['payment_status'] ?? $payload['status'] ?? ''));

        if (($providerId === '' && $subscriptionId === '' && $orderId === '') || $providerStatus === '') {
            return new WebhookResult(422, 'invalid_payload');
        }

        if (!Status::supported($providerStatus)) {
            return new WebhookResult(422, 'unsupported_status');
        }

        $hash = Signature::payloadHash($payload);
        $pdo = DB::get_db();
        $pdo->beginTransaction();

        try {
            $transactionId = $this->lockTransaction($pdo, $providerId, $subscriptionId, $orderId);

            if ($event = DB::table('nowpayments_events')->where('payload_hash', $hash)->first()) {
                $pdo->commit();
                return new WebhookResult(200, 'duplicate');
            }

            if ($transactionId === null) {
                $this->recordEvent(null, $providerId, $hash, $providerStatus, 'rejected', 'unknown_transaction', $payload);
                $pdo->commit();
                return new WebhookResult(202, 'unknown_transaction');
            }

            $transaction = DB::table('nowpayments_transactions')->where('id', $transactionId)->first();

            if (!$transaction || !$this->matches($transaction, $payload)) {
                $this->recordEvent($transactionId, $providerId, $hash, $providerStatus, 'rejected', 'context_mismatch', $payload);
                $pdo->commit();
                return new WebhookResult(422, 'context_mismatch');
            }

            $normalized = Status::normalize($providerStatus);

            if (!Status::canTransition((string) $transaction->status, $normalized)) {
                $this->recordEvent($transactionId, $providerId, $hash, $providerStatus, 'ignored', 'invalid_transition', $payload);
                $pdo->commit();
                return new WebhookResult(200, 'ignored_transition');
            }

            $transaction->provider_status = $providerStatus;
            $transaction->status = $normalized;
            $transaction->received_amount = self::amount($payload['actually_paid'] ?? $payload['amount_received'] ?? 0);
            $transaction->outcome_amount = self::amount($payload['outcome_amount'] ?? 0);
            $transaction->last_checked_at = Helper::dtime();
            $transaction->next_retry_at = Status::isTerminal($normalized) ? null : Helper::dtime('+5 minutes');
            $transaction->updated_at = Helper::dtime();
            $transaction->save();

            $applied = null;

            if (EntitlementService::shouldApply((string) $transaction->mode, $normalized)) {
                $applied = $this->entitlements->apply($transaction, $payload);
            }

            $this->recordEvent($transactionId, $providerId, $hash, $providerStatus, 'processed', null, $payload);
            $pdo->commit();

            if ($applied !== null) {
                $user = DB::user()->where('id', $applied['user_id'])->first();
                \Core\Plugin::dispatch('payment.success', [$user, $applied['plan_id'], $applied['payment_id']]);
            }

            return new WebhookResult(
                200,
                'processed',
                $applied['user_id'] ?? null,
                $applied['plan_id'] ?? null,
                $applied['payment_id'] ?? null
            );
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function lockTransaction(\PDO $pdo, string $providerId, string $subscriptionId, string $orderId): ?int
    {
        $table = DBprefix.'nowpayments_transactions';

        if ($providerId !== '') {
            $statement = $pdo->prepare("SELECT `id` FROM `{$table}` WHERE `provider_payment_id` = ? FOR UPDATE");
            $statement->execute([$providerId]);
            $id = $statement->fetchColumn();

            if ($id !== false) {
                return (int) $id;
            }
        }

        if ($orderId !== '') {
            $statement = $pdo->prepare("SELECT `id` FROM `{$table}` WHERE `order_id` = ? FOR UPDATE");
            $statement->execute([$orderId]);
            $id = $statement->fetchColumn();

            if ($id !== false) {
                return (int) $id;
            }
        }

        if ($subscriptionId !== '') {
            $statement = $pdo->prepare("SELECT `id` FROM `{$table}` WHERE `provider_subscription_id` = ? FOR UPDATE");
            $statement->execute([$subscriptionId]);
            $id = $statement->fetchColumn();

            if ($id !== false) {
                return (int) $id;
            }
        }

        return null;
    }

    private function matches(object $transaction, array $payload): bool
    {
        if (isset($payload['order_id']) && (string) $payload['order_id'] !== (string) $transaction->order_id) {
            return false;
        }

        if (isset($payload['payment_id']) && (string) $payload['payment_id'] !== (string) $transaction->provider_payment_id) {
            return false;
        }

        if (isset($payload['subscription_id']) && (string) $payload['subscription_id'] !== (string) $transaction->provider_subscription_id) {
            return false;
        }

        if (isset($payload['price_currency'])
            && strtoupper((string) $payload['price_currency']) !== strtoupper((string) $transaction->price_currency)) {
            return false;
        }

        if (isset($payload['price_amount'])
            && abs((float) $payload['price_amount'] - (float) $transaction->expected_amount) > 0.01) {
            return false;
        }

        return true;
    }

    private function recordEvent(?int $transactionId, string $providerId, string $hash, string $status, string $result, ?string $reason, array $payload): void
    {
        $event = DB::table('nowpayments_events')->create();
        $event->transaction_id = $transactionId;
        $event->provider_payment_id = $providerId;
        $event->payload_hash = $hash;
        $event->signature_verified = 1;
        $event->status = $status;
        $event->result = $result;
        $event->failure_reason = $reason;
        $event->payload = json_encode(self::sanitizedPayload($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $event->received_at = Helper::dtime();
        $event->processed_at = Helper::dtime();
        $event->save();
    }

    /** @return array<string, mixed> */
    private static function sanitizedPayload(array $payload): array
    {
        return array_intersect_key($payload, array_flip([
            'payment_id', 'subscription_id', 'payment_status', 'status', 'order_id', 'price_amount', 'price_currency',
            'pay_amount', 'pay_currency', 'actually_paid', 'amount_received', 'outcome_amount',
            'outcome_currency', 'purchase_id', 'parent_payment_id', 'updated_at', 'created_at',
        ]));
    }

    private static function amount(mixed $amount): string
    {
        return is_numeric($amount) ? number_format((float) $amount, 8, '.', '') : '0.00000000';
    }
}
