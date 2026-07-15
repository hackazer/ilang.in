<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class SubscriptionService
{
    public function __construct(private readonly Client $client)
    {
    }

    public function enroll(object $user, object $plan, string $term, string $mode, PricingResult $pricing, array $settings, string $attemptId): PrepaidAttempt
    {
        $auth = $this->client->authenticate((string) $settings['dashboard_email'], (string) $settings['dashboard_password']);
        $jwt = (string) ($auth['token'] ?? $auth['result']['token'] ?? '');

        if ($jwt === '') {
            throw new \UnexpectedValueException('NOWPayments dashboard authentication did not return a token.');
        }

        $definition = RecurringPlan::define((int) $plan->id, (string) $plan->name, $term, $mode, $pricing->decimal(), (string) $settings['settlement_currency']);
        $remotePlanId = (new PlanManager($this->client))->sync($definition, $jwt);
        $order = Order::fromAttempt((int) $user->id, (int) $plan->id, $term, $mode, $attemptId);
        $subPartnerId = null;

        if ($mode === 'custodial') {
            $subPartnerId = (new CustomerManager($this->client))->provision((int) $user->id, $jwt);
        }

        $attempt = $this->createPending($user, $plan, $term, $mode, $pricing, $order, $remotePlanId, $subPartnerId);

        try {
            $payload = ['subscription_plan_id' => $remotePlanId, 'email' => (string) $user->email];

            if ($subPartnerId !== null) {
                $payload['sub_partner_id'] = $subPartnerId;
            }

            $response = $this->client->createSubscription($payload, $jwt);
            $result = isset($response['result']) && is_array($response['result']) ? $response['result'] : $response;
            $remoteSubscriptionId = (string) ($result['id'] ?? '');

            if ($remoteSubscriptionId === '') {
                throw new \UnexpectedValueException('NOWPayments did not return a subscription identifier.');
            }

            $transaction = DB::table('nowpayments_transactions')->where('id', $attempt->transactionId())->first();
            $transaction->provider_subscription_id = $remoteSubscriptionId;
            $transaction->provider_status = (string) ($result['status'] ?? 'WAITING_PAY');
            $transaction->status = Status::normalize($transaction->provider_status);
            $transaction->expires_at = isset($result['expire_date']) && strtotime((string) $result['expire_date']) !== false ? date('Y-m-d H:i:s', strtotime((string) $result['expire_date'])) : null;
            $transaction->metadata = json_encode(array_replace($pricing->metadata(), ['remote_plan_id' => $remotePlanId, 'subscriber' => $mode]), JSON_THROW_ON_ERROR);
            $transaction->updated_at = Helper::dtime();
            $transaction->save();

            $subscription = DB::subscription()->where('id', $attempt->subscriptionId())->first();
            $subscription->data = json_encode(['type' => 'nowpayments', 'paymentmethod' => 'nowpayments', 'mode' => $mode, 'provider_subscription_id' => $remoteSubscriptionId], JSON_THROW_ON_ERROR);
            $subscription->save();

            return new PrepaidAttempt($attempt->transactionId(), $attempt->subscriptionId(), $attempt->orderId(), $attempt->idempotencyKey(), (string) $transaction->status, null, $result);
        } catch (\Throwable $exception) {
            (new DatabasePrepaidStore())->markFailed($attempt);
            throw $exception;
        }
    }

    private function createPending(object $user, object $plan, string $term, string $mode, PricingResult $pricing, Order $order, string $remotePlanId, ?string $subPartnerId): PrepaidAttempt
    {
        $pdo = DB::get_db();
        $pdo->beginTransaction();

        try {
            $subscription = DB::subscription()->create();
            $subscription->tid = null;
            $subscription->userid = $user->id;
            $subscription->plan = $term;
            $subscription->planid = $plan->id;
            $subscription->status = 'Pending';
            $subscription->amount = $pricing->decimal();
            $subscription->date = Helper::dtime();
            $subscription->expiry = Helper::dtime();
            $subscription->lastpayment = Helper::dtime();
            $subscription->data = json_encode(['type' => 'nowpayments', 'mode' => $mode], JSON_THROW_ON_ERROR);
            $subscription->uniqueid = $order->id();
            $subscription->save();

            $transaction = DB::table('nowpayments_transactions')->create();
            $transaction->userid = $user->id;
            $transaction->planid = $plan->id;
            $transaction->subscriptionid = $subscription->id();
            $transaction->order_id = $order->id();
            $transaction->idempotency_key = $order->idempotencyKey();
            $transaction->mode = $mode;
            $transaction->term = $term;
            $transaction->price_currency = strtoupper((string) config('currency'));
            $transaction->settlement_currency = strtoupper((string) config('currency'));
            $transaction->expected_amount = $pricing->decimal();
            $transaction->status = Status::PENDING;
            $transaction->retry_count = 0;
            $transaction->next_retry_at = Helper::dtime('+2 minutes');
            $transaction->metadata = json_encode(array_replace($pricing->metadata(), ['remote_plan_id' => $remotePlanId, 'sub_partner_id' => $subPartnerId]), JSON_THROW_ON_ERROR);
            $transaction->created_at = Helper::dtime();
            $transaction->updated_at = Helper::dtime();
            $transaction->save();
            $pdo->commit();

            return new PrepaidAttempt((int) $transaction->id, (int) $subscription->id(), $order->id(), $order->idempotencyKey(), Status::PENDING);
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }
}
