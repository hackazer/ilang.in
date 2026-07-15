<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class Reconciler
{
    private ?string $jwt = null;

    public function __construct(private readonly Client $client, private readonly array $settings)
    {
    }

    public function run(int $limit = 50): array
    {
        $discovered = $this->discoverRecurring(min(100, max(10, $limit)));
        $transactions = DB::table('nowpayments_transactions')
            ->whereRaw("`status` IN ('pending','confirming','partial') AND `next_retry_at` IS NOT NULL AND `next_retry_at` <= ?", [Helper::dtime()])
            ->orderByAsc('next_retry_at')
            ->limit(max(1, min(100, $limit)))
            ->findMany();
        $result = ['discovered' => $discovered, 'checked' => 0, 'processed' => 0, 'failed' => 0];

        foreach ($transactions as $transaction) {
            $result['checked']++;

            try {
                $payload = $transaction->provider_payment_id
                    ? $this->client->payment((string) $transaction->provider_payment_id)
                    : $this->recurringPayload($transaction);
                $payload['order_id'] ??= (string) $transaction->order_id;
                $webhookResult = (new WebhookService(new EntitlementService()))->handleTrusted($payload);

                if ($webhookResult->httpStatus < 300) {
                    $result['processed']++;
                }
            } catch (\Throwable) {
                $result['failed']++;
                $attempts = (int) $transaction->retry_count + 1;
                $delay = min(3600, (2 ** min($attempts, 10)) * 30 + random_int(0, 30));
                $transaction->retry_count = $attempts;
                $transaction->last_checked_at = Helper::dtime();
                $transaction->next_retry_at = Helper::dtime('+'.$delay.' seconds');
                $transaction->updated_at = Helper::dtime();
                $transaction->save();
            }
        }

        return $result;
    }

    private function discoverRecurring(int $limit): int
    {
        if (empty($this->settings['dashboard_email']) || empty($this->settings['dashboard_password'])) {
            return 0;
        }

        $created = 0;
        $mappings = DB::table('nowpayments_plans')->where('active', 1)->limit(50)->findMany();

        foreach ($mappings as $mapping) {
            $response = $this->client->subscriptions($this->jwt(), [
                'subscription_plan_id' => (string) $mapping->remote_plan_id,
                'limit' => $limit,
                'offset' => 0,
            ]);
            $rows = $response['result'] ?? [];

            if (isset($rows['id'])) $rows = [$rows];
            if (!is_array($rows)) continue;

            foreach ($rows as $row) {
                if (!is_array($row) || empty($row['id'])) continue;
                $remoteId = (string) $row['id'];
                if (DB::table('nowpayments_transactions')->where('provider_subscription_id', $remoteId)->first()) continue;

                $user = $this->subscriber($row['subscriber'] ?? []);
                if (!$user) continue;
                $subscription = DB::subscription()->where('userid', $user->id)->where('planid', $mapping->planid)->orderByDesc('id')->first();
                if (!$subscription) continue;

                $digest = hash('sha256', 'recurring|'.$remoteId);
                $transaction = DB::table('nowpayments_transactions')->create();
                $transaction->userid = $user->id;
                $transaction->planid = $mapping->planid;
                $transaction->subscriptionid = $subscription->id;
                $transaction->order_id = 'np-recurring-'.substr($digest, 0, 40);
                $transaction->idempotency_key = $digest;
                $transaction->provider_subscription_id = $remoteId;
                $transaction->mode = $mapping->mode;
                $transaction->term = $mapping->term;
                $transaction->price_currency = $mapping->currency;
                $transaction->settlement_currency = $mapping->currency;
                $transaction->expected_amount = $mapping->amount;
                $transaction->status = Status::PENDING;
                $transaction->provider_status = (string) ($row['status'] ?? 'WAITING_PAY');
                $transaction->retry_count = 0;
                $transaction->next_retry_at = Helper::dtime();
                $transaction->metadata = json_encode(['remote_plan_id' => $mapping->remote_plan_id, 'discovered' => true], JSON_THROW_ON_ERROR);
                $transaction->created_at = Helper::dtime();
                $transaction->updated_at = Helper::dtime();
                $transaction->save();
                $created++;
            }
        }

        return $created;
    }

    private function subscriber(mixed $subscriber): ?object
    {
        if (!is_array($subscriber)) return null;

        if (!empty($subscriber['email'])) {
            return DB::user()->where('email', (string) $subscriber['email'])->first() ?: null;
        }

        if (!empty($subscriber['sub_partner_id'])) {
            $customer = DB::table('nowpayments_customers')->where('provider_subpartner_id', (string) $subscriber['sub_partner_id'])->first();
            return $customer ? DB::user()->where('id', $customer->userid)->first() ?: null : null;
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function recurringPayload(object $transaction): array
    {
        if (!$transaction->provider_subscription_id) {
            throw new \RuntimeException('NOWPayments transaction has no provider identifier.');
        }

        $response = $this->client->subscription((string) $transaction->provider_subscription_id, $this->jwt());
        $result = isset($response['result']) && is_array($response['result']) ? $response['result'] : $response;

        return [
            'subscription_id' => (string) ($result['id'] ?? $transaction->provider_subscription_id),
            'status' => (string) ($result['status'] ?? ''),
            'price_amount' => (string) $transaction->expected_amount,
            'price_currency' => (string) $transaction->price_currency,
            'updated_at' => $result['updated_at'] ?? null,
        ];
    }

    private function jwt(): string
    {
        if ($this->jwt !== null) return $this->jwt;

        $response = $this->client->authenticate((string) ($this->settings['dashboard_email'] ?? ''), (string) ($this->settings['dashboard_password'] ?? ''));
        $this->jwt = (string) ($response['token'] ?? $response['result']['token'] ?? '');

        if ($this->jwt === '') {
            throw new \RuntimeException('NOWPayments reconciliation authentication failed.');
        }

        return $this->jwt;
    }
}
