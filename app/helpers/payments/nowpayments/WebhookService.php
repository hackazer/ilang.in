<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class WebhookService
{
    public const SOURCE_IPN = 'ipn';
    public const SOURCE_RECONCILIATION = 'reconciliation';

    public function __construct(private readonly EntitlementService $entitlements = new EntitlementService())
    {
    }

    public function handle(array $payload, string $providedSignature, string $secret): WebhookResult
    {
        if (!Signature::verify($payload, $providedSignature, $secret)) {
            return new WebhookResult(401, 'invalid_signature');
        }

        return $this->process($payload, self::SOURCE_IPN);
    }

    public function handleTrusted(array $payload): WebhookResult
    {
        return $this->process($payload, self::SOURCE_RECONCILIATION);
    }

    private function process(array $payload, string $source): WebhookResult
    {
        $context = self::providerContext($payload, $source);

        if ($context === null) {
            return new WebhookResult(422, 'invalid_payload');
        }

        $providerStatus = $context['status'];

        if (!Status::supported($providerStatus)) {
            return new WebhookResult(422, 'unsupported_status');
        }

        $normalized = Status::normalize($providerStatus);

        if ($normalized === Status::PAID && !$context['complete']) {
            return new WebhookResult(422, 'insufficient_provider_context');
        }

        $hash = hash('sha256', $source.'|'.Signature::canonicalJson($payload));
        $pdo = DB::get_db();
        $pdo->beginTransaction();
        $transactionId = null;

        try {
            $transactionId = $this->lockTransaction($pdo, $context);

            if ($event = DB::table('nowpayments_events')->where('payload_hash', $hash)->first()) {
                $transactionId = $event->transaction_id ? (int) $event->transaction_id : $transactionId;
                $pdo->commit();
                $this->dispatchPendingSuccess($transactionId);

                return new WebhookResult(200, 'duplicate');
            }

            if ($transactionId === null) {
                $this->recordEvent(null, $context['provider_id'], $hash, $providerStatus, 'rejected', 'unknown_transaction', $payload, $source);
                $pdo->commit();

                return new WebhookResult(202, 'unknown_transaction');
            }

            $transaction = DB::table('nowpayments_transactions')->where('id', $transactionId)->first();

            if (!$transaction || !$this->matches($transaction, $context)) {
                $this->recordEvent($transactionId, $context['provider_id'], $hash, $providerStatus, 'rejected', 'context_mismatch', $payload, $source);
                $pdo->commit();

                return new WebhookResult(422, 'context_mismatch');
            }

            if (!Status::canTransition((string) $transaction->status, $normalized)) {
                $this->recordEvent($transactionId, $context['provider_id'], $hash, $providerStatus, 'ignored', 'invalid_transition', $payload, $source);
                $pdo->commit();

                return new WebhookResult(200, 'ignored_transition');
            }

            $transaction->provider_status = $providerStatus;
            $transaction->status = $normalized;

            if ($context['received_amount'] !== null) {
                $transaction->received_amount = $context['received_amount'];
            }

            if ($context['outcome_amount'] !== null) {
                $transaction->outcome_amount = $context['outcome_amount'];
            }

            $transaction->last_checked_at = Helper::dtime();
            $transaction->next_retry_at = Status::isTerminal($normalized) ? null : Helper::dtime('+5 minutes');
            $transaction->updated_at = Helper::dtime();
            $transaction->save();

            $applied = null;

            if (EntitlementService::shouldApply((string) $transaction->mode, $normalized)) {
                $applied = $this->entitlements->apply($transaction, $payload);
            }

            $this->recordEvent($transactionId, $context['provider_id'], $hash, $providerStatus, 'processed', null, $payload, $source);

            if ($applied !== null) {
                $this->queueSuccess($transactionId, $applied);
            }

            $pdo->commit();
            $this->dispatchPendingSuccess($transactionId);

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

    /**
     * @return array{
     *   kind:string,
     *   provider_id:string,
     *   payment_id:string,
     *   subscription_id:string,
     *   order_id:string,
     *   status:string,
     *   amount:?string,
     *   currency:string,
     *   received_amount:?string,
     *   outcome_amount:?string,
     *   complete:bool
     * }|null
     */
    private static function providerContext(array $payload, string $source): ?array
    {
        $paymentId = trim((string) ($payload['payment_id'] ?? ''));

        if ($paymentId !== '' || array_key_exists('payment_status', $payload)) {
            $status = trim((string) ($payload['payment_status'] ?? ''));

            if ($paymentId === '' || $status === '') {
                return null;
            }

            $amount = array_key_exists('price_amount', $payload)
                ? EntitlementService::normalizeDecimal($payload['price_amount'])
                : null;
            $currency = trim((string) ($payload['price_currency'] ?? ''));

            return [
                'kind' => 'payment',
                'provider_id' => $paymentId,
                'payment_id' => $paymentId,
                'subscription_id' => '',
                'order_id' => trim((string) ($payload['order_id'] ?? '')),
                'status' => $status,
                'amount' => $amount,
                'currency' => $currency,
                'received_amount' => self::firstDecimal($payload, ['actually_paid', 'amount_received']),
                'outcome_amount' => self::firstDecimal($payload, ['outcome_amount']),
                'complete' => $amount !== null && $currency !== '',
            ];
        }

        $officialId = trim((string) ($payload['id'] ?? ''));
        $subscriptionId = $officialId !== '' ? $officialId : trim((string) ($payload['subscription_id'] ?? ''));
        $status = trim((string) ($payload['status'] ?? ''));

        if ($subscriptionId === '' || $status === '') {
            return null;
        }

        $amount = array_key_exists('amount', $payload)
            ? EntitlementService::normalizeDecimal($payload['amount'])
            : null;
        $currency = trim((string) ($payload['currency'] ?? ''));
        $providerEvidence = $source === self::SOURCE_IPN || (
            $officialId !== ''
            && (array_key_exists('subscription_plan_id', $payload)
                || array_key_exists('subscriber', $payload)
                || array_key_exists('is_active', $payload)
                || array_key_exists('expire_date', $payload))
        );

        return [
            'kind' => 'recurring',
            'provider_id' => $subscriptionId,
            'payment_id' => '',
            'subscription_id' => $subscriptionId,
            'order_id' => '',
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
            'received_amount' => $amount,
            'outcome_amount' => null,
            'complete' => $providerEvidence && $amount !== null && $currency !== '',
        ];
    }

    /** @param array<string, mixed> $context */
    private function lockTransaction(\PDO $pdo, array $context): ?int
    {
        $table = DBprefix.'nowpayments_transactions';
        $lock = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';

        if ($context['payment_id'] !== '') {
            $statement = $pdo->prepare("SELECT `id` FROM `{$table}` WHERE `provider_payment_id` = ?{$lock}");
            $statement->execute([$context['payment_id']]);
            $id = $statement->fetchColumn();

            if ($id !== false) {
                return (int) $id;
            }
        }

        if ($context['subscription_id'] !== '') {
            $statement = $pdo->prepare("SELECT `id` FROM `{$table}` WHERE `provider_subscription_id` = ?{$lock}");
            $statement->execute([$context['subscription_id']]);
            $id = $statement->fetchColumn();

            if ($id !== false) {
                return (int) $id;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $context */
    private function matches(object $transaction, array $context): bool
    {
        if ($context['payment_id'] !== ''
            && (string) $context['payment_id'] !== (string) $transaction->provider_payment_id) {
            return false;
        }

        if ($context['subscription_id'] !== ''
            && (string) $context['subscription_id'] !== (string) $transaction->provider_subscription_id) {
            return false;
        }

        if ($context['order_id'] !== ''
            && (string) $context['order_id'] !== (string) $transaction->order_id) {
            return false;
        }

        if ($context['amount'] !== null
            && !EntitlementService::decimalEquals($context['amount'], $transaction->expected_amount)) {
            return false;
        }

        if ($context['currency'] !== '') {
            $expectedCurrency = $context['kind'] === 'recurring' && trim((string) $transaction->settlement_currency) !== ''
                ? (string) $transaction->settlement_currency
                : (string) $transaction->price_currency;

            if (strcasecmp($context['currency'], $expectedCurrency) !== 0) {
                return false;
            }
        }

        return true;
    }

    /** @param array{user_id:int, plan_id:int, payment_id:int} $applied */
    private function queueSuccess(int $transactionId, array $applied): void
    {
        if (DB::table('nowpayments_outbox')->where('transaction_id', $transactionId)->first()) {
            return;
        }

        $outbox = DB::nowpayments_outbox()->create();
        $outbox->event_key = 'payment.success:'.$transactionId;
        $outbox->transaction_id = $transactionId;
        $outbox->userid = $applied['user_id'];
        $outbox->planid = $applied['plan_id'];
        $outbox->paymentid = $applied['payment_id'];
        $outbox->status = 'pending';
        $outbox->attempts = 0;
        $outbox->available_at = Helper::dtime();
        $outbox->created_at = Helper::dtime();
        $outbox->updated_at = Helper::dtime();
        $outbox->save();
    }

    private function dispatchPendingSuccess(?int $transactionId): void
    {
        if ($transactionId === null) {
            return;
        }

        $pdo = DB::get_db();
        $pdo->beginTransaction();
        $outboxId = null;

        try {
            $table = DBprefix.'nowpayments_outbox';
            $lock = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
            $statement = $pdo->prepare("SELECT `id` FROM `{$table}` WHERE `transaction_id` = ? AND `status` = 'pending' AND `available_at` <= ?{$lock}");
            $statement->execute([$transactionId, Helper::dtime()]);
            $id = $statement->fetchColumn();

            if ($id === false) {
                $pdo->commit();
                return;
            }

            $outboxId = (int) $id;
            $outbox = DB::table('nowpayments_outbox')->where('id', $outboxId)->first();
            $user = $outbox ? DB::user()->where('id', $outbox->userid)->first() : null;

            if (!$outbox || !$user) {
                throw new \RuntimeException('NOWPayments outbox billing context is missing.');
            }

            \Core\Plugin::dispatch('payment.success', [
                $user,
                (int) $outbox->planid,
                (int) $outbox->paymentid,
                (string) $outbox->event_key,
            ]);

            $outbox->status = 'dispatched';
            $outbox->attempts = (int) $outbox->attempts + 1;
            $outbox->dispatched_at = Helper::dtime();
            $outbox->last_error = null;
            $outbox->updated_at = Helper::dtime();
            $outbox->save();
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($outboxId !== null) {
                $this->recordDispatchFailure($outboxId, $exception);
            }

            throw $exception;
        }
    }

    private function recordDispatchFailure(int $outboxId, \Throwable $exception): void
    {
        if ($outbox = DB::table('nowpayments_outbox')->where('id', $outboxId)->first()) {
            $outbox->attempts = (int) $outbox->attempts + 1;
            $outbox->last_error = substr($exception::class, 0, 191);
            $outbox->available_at = Helper::dtime();
            $outbox->updated_at = Helper::dtime();
            $outbox->save();
        }
    }

    private function recordEvent(
        ?int $transactionId,
        string $providerId,
        string $hash,
        string $status,
        string $result,
        ?string $reason,
        array $payload,
        string $source
    ): void {
        $event = DB::nowpayments_events()->create();
        $event->transaction_id = $transactionId;
        $event->provider_payment_id = $providerId;
        $event->payload_hash = $hash;
        $event->signature_verified = $source === self::SOURCE_IPN ? 1 : 0;
        $event->source = $source;
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
            'id', 'payment_id', 'subscription_id', 'payment_status', 'status', 'order_id', 'price_amount',
            'price_currency', 'currency', 'amount', 'pay_amount', 'pay_currency', 'actually_paid',
            'amount_received', 'outcome_amount', 'outcome_currency', 'purchase_id', 'parent_payment_id',
            'subscription_plan_id', 'is_active', 'expire_date', 'updated_at', 'created_at',
        ]));
    }

    /** @param list<string> $keys */
    private static function firstDecimal(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return EntitlementService::normalizeDecimal($payload[$key]);
            }
        }

        return null;
    }
}
