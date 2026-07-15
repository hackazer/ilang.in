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
        $transactions = DB::table('nowpayments_transactions')
            ->whereRaw("`status` IN ('pending','confirming','partial') AND `next_retry_at` IS NOT NULL AND `next_retry_at` <= ?", [Helper::dtime()])
            ->orderByAsc('next_retry_at')
            ->limit(max(1, min(100, $limit)))
            ->findMany();
        $result = ['checked' => 0, 'processed' => 0, 'failed' => 0];

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
