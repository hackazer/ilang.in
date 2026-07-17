<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class PlanManager
{
    public function __construct(private readonly Client $client)
    {
    }

    public function sync(RecurringPlan $definition, string $jwt): string
    {
        $mapping = DB::table('nowpayments_plans')->where('mapping_key', $definition->mappingKey)->first();

        if ($mapping && $mapping->remote_plan_id && hash_equals((string) $mapping->sync_hash, $definition->syncHash)) {
            return (string) $mapping->remote_plan_id;
        }

        $response = $mapping && $mapping->remote_plan_id
            ? $this->client->updatePlan((string) $mapping->remote_plan_id, $definition->payload(), $jwt)
            : $this->client->createPlan($definition->payload(), $jwt);
        $result = self::result($response);
        $remoteId = (string) ($result['id'] ?? $mapping->remote_plan_id ?? '');

        if ($remoteId === '') {
            throw new \UnexpectedValueException('NOWPayments did not return a recurring plan identifier.');
        }

        $mapping ??= DB::table('nowpayments_plans')->create();
        $mapping->mapping_key = $definition->mappingKey;
        $mapping->planid = $definition->planId;
        $mapping->term = $definition->term;
        $mapping->mode = $definition->mode;
        $mapping->remote_plan_id = $remoteId;
        $mapping->amount = $definition->amount;
        $mapping->currency = $definition->currency;
        $mapping->interval_days = $definition->intervalDays;
        $mapping->sync_hash = $definition->syncHash;
        $mapping->active = 1;
        $mapping->metadata = json_encode(array_intersect_key($result, array_flip(['id', 'title', 'interval_day', 'amount', 'currency', 'ipn_callback_url'])), JSON_THROW_ON_ERROR);
        $mapping->last_synced_at = Helper::dtime();
        $mapping->created_at = $mapping->created_at ?: Helper::dtime();
        $mapping->updated_at = Helper::dtime();
        $mapping->save();

        return $remoteId;
    }

    /** @return array<string, mixed> */
    private static function result(array $response): array
    {
        return isset($response['result']) && is_array($response['result']) ? $response['result'] : $response;
    }
}
