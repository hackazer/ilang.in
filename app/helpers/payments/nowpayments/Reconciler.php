<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class Reconciler
{
    private const REMOTE_PAGE_SIZE = 50;
    private const MAX_REMOTE_PAGES_PER_TRANSACTION = 3;
    private const LOCAL_MAPPING_PAGE_SIZE = 25;
    private const MAX_LOCAL_MAPPINGS = 100;
    private const MAX_REMOTE_PAGES_PER_MAPPING = 2;

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
                if ($transaction->provider_payment_id) {
                    $payload = $this->client->payment((string) $transaction->provider_payment_id);
                } elseif ($transaction->provider_subscription_id) {
                    $payload = $this->recurringPayload($transaction);
                } else {
                    $payload = $this->recoverPrepaidPayload($transaction, $limit);
                }

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
    private function recoverPrepaidPayload(object $transaction, int $limit): array
    {
        if (empty($this->settings['dashboard_email']) || empty($this->settings['dashboard_password'])) {
            throw new \RuntimeException('NOWPayments payment recovery requires dashboard credentials.');
        }

        $pageSize = min(self::REMOTE_PAGE_SIZE, max(10, $limit));
        $metadata = self::metadata($transaction);
        $page = max(0, (int) ($metadata['reconciliation_payment_page'] ?? 0));

        for ($pages = 0; $pages < self::MAX_REMOTE_PAGES_PER_TRANSACTION; $pages++, $page++) {
            $response = $this->client->payments([
                'limit' => $pageSize,
                'page' => $page,
                'sortBy' => 'created_at',
                'orderBy' => 'desc',
            ], $this->jwt());
            $rows = self::rows($response);

            foreach ($rows as $row) {
                if ((string) ($row['order_id'] ?? '') !== (string) $transaction->order_id) {
                    continue;
                }

                if (trim((string) ($row['payment_id'] ?? '')) === '') {
                    continue;
                }

                $transaction->provider_payment_id = (string) $row['payment_id'];
                $transaction->provider_status = (string) ($row['payment_status'] ?? '');
                unset($metadata['reconciliation_payment_page']);
                $transaction->metadata = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                $transaction->updated_at = Helper::dtime();
                $transaction->save();

                return $row;
            }

            if (count($rows) < $pageSize) {
                $page = 0;
                break;
            }
        }

        $metadata['reconciliation_payment_page'] = $page;
        $transaction->metadata = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $transaction->updated_at = Helper::dtime();
        $transaction->save();

        throw new \RuntimeException('NOWPayments payment is not visible in provider history yet.');
    }

    /** @return list<array<string, mixed>> */
    private static function rows(array $response): array
    {
        $rows = $response['data'] ?? $response['result'] ?? $response;

        if (is_array($rows) && (isset($rows['id']) || isset($rows['payment_id']))) {
            $rows = [$rows];
        }

        if (!is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /** @return array<string, mixed> */
    private static function metadata(object $record): array
    {
        $metadata = json_decode((string) ($record->metadata ?? ''), true);

        return is_array($metadata) ? $metadata : [];
    }

    private function discoverRecurring(int $limit): int
    {
        if (empty($this->settings['dashboard_email']) || empty($this->settings['dashboard_password'])) {
            return 0;
        }

        $created = 0;
        $mappingLimit = min(self::MAX_LOCAL_MAPPINGS, max(10, $limit));
        $requestBudget = min(100, max(10, $limit));
        $cursorOwner = DB::table('nowpayments_plans')
            ->where('active', 1)
            ->orderByAsc('id')
            ->first();

        if (!$cursorOwner) {
            return 0;
        }

        $mappingOffset = max(0, (int) (self::metadata($cursorOwner)['reconciliation_mapping_offset'] ?? 0));
        $nextOffset = $mappingOffset;
        $scanned = 0;
        $wrapped = false;

        while ($scanned < $mappingLimit && $requestBudget > 0 && $created < $limit) {
            $pageSize = min(self::LOCAL_MAPPING_PAGE_SIZE, $mappingLimit - $scanned);
            $mappings = DB::table('nowpayments_plans')
                ->where('active', 1)
                ->orderByAsc('id')
                ->limit($pageSize)
                ->offset($mappingOffset)
                ->findMany();

            if ($mappings === []) {
                if ($mappingOffset > 0 && !$wrapped) {
                    $mappingOffset = 0;
                    $nextOffset = 0;
                    $wrapped = true;
                    continue;
                }

                $nextOffset = 0;
                break;
            }

            foreach ($mappings as $index => $mapping) {
                $created += $this->discoverRecurringForMapping(
                    $mapping,
                    $limit - $created,
                    $requestBudget
                );
                $scanned++;
                $nextOffset = $mappingOffset + $index + 1;

                if ($requestBudget <= 0 || $created >= $limit) {
                    break 2;
                }
            }

            $mappingOffset += count($mappings);

            if (count($mappings) < $pageSize) {
                $nextOffset = 0;
                break;
            }
        }

        $this->saveMappingCursor((int) $cursorOwner->id, $nextOffset);

        return $created;
    }

    private function saveMappingCursor(int $mappingId, int $offset): void
    {
        $mapping = DB::table('nowpayments_plans')->where('id', $mappingId)->first();

        if (!$mapping) {
            return;
        }

        $metadata = self::metadata($mapping);

        if ($offset === 0) {
            unset($metadata['reconciliation_mapping_offset']);
        } else {
            $metadata['reconciliation_mapping_offset'] = $offset;
        }

        $mapping->metadata = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $mapping->updated_at = Helper::dtime();
        $mapping->save();
    }

    private function discoverRecurringForMapping(object $mapping, int $remaining, int &$requestBudget): int
    {
        $created = 0;
        $pageSize = self::REMOTE_PAGE_SIZE;
        $metadata = self::metadata($mapping);
        $offset = max(0, (int) ($metadata['reconciliation_subscription_offset'] ?? 0));

        for ($page = 0; $page < self::MAX_REMOTE_PAGES_PER_MAPPING && $requestBudget > 0; $page++) {
            $requestBudget--;
            $response = $this->client->subscriptions($this->jwt(), [
                'subscription_plan_id' => (string) $mapping->remote_plan_id,
                'limit' => $pageSize,
                'offset' => $offset,
            ]);
            $rows = self::rows($response);

            foreach ($rows as $index => $row) {
                if ($this->discoverRecurringRow($mapping, $row)) {
                    $created++;
                }

                if ($created >= $remaining) {
                    $offset += $index + 1;
                    break 2;
                }
            }

            if (count($rows) < $pageSize) {
                $offset = 0;
                break;
            }

            $offset += $pageSize;
        }

        if ($offset === 0) {
            unset($metadata['reconciliation_subscription_offset']);
        } else {
            $metadata['reconciliation_subscription_offset'] = $offset;
        }

        $mapping->metadata = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $mapping->updated_at = Helper::dtime();
        $mapping->save();

        return $created;
    }

    /** @param array<string, mixed> $row */
    private function discoverRecurringRow(object $mapping, array $row): bool
    {
        if (empty($row['id'])) {
            return false;
        }

        $remoteId = (string) $row['id'];

        if (DB::table('nowpayments_transactions')->where('provider_subscription_id', $remoteId)->first()) {
            return false;
        }

        $user = $this->subscriber($row['subscriber'] ?? []);

        if (!$user) {
            return false;
        }

        $subscription = DB::subscription()
            ->where('userid', $user->id)
            ->where('planid', $mapping->planid)
            ->orderByDesc('id')
            ->first();

        if (!$subscription) {
            return false;
        }

        $digest = hash('sha256', 'recurring|'.$remoteId);
        $transaction = DB::table('nowpayments_transactions')->create([]);
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

        try {
            $transaction->save();
        } catch (\Throwable $exception) {
            if (DB::table('nowpayments_transactions')->where('provider_subscription_id', $remoteId)->first()) {
                return false;
            }

            throw $exception;
        }

        return true;
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
        $result['subscription_id'] = (string) ($result['subscription_id'] ?? $result['id'] ?? $transaction->provider_subscription_id);
        $result['status'] = (string) ($result['status'] ?? '');

        if (array_key_exists('amount', $result) && !array_key_exists('price_amount', $result)) {
            $result['price_amount'] = $result['amount'];
        }

        if (array_key_exists('currency', $result) && !array_key_exists('price_currency', $result)) {
            $result['price_currency'] = $result['currency'];
        }

        return $result;
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
