<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class CustomerManager
{
    public function __construct(private readonly Client $client)
    {
    }

    public function provision(int $userId, string $jwt): string
    {
        if ($customer = DB::table('nowpayments_customers')->where('userid', $userId)->first()) {
            if ($customer->provider_subpartner_id) {
                return (string) $customer->provider_subpartner_id;
            }
        }

        $name = substr('ilangin-u'.$userId.'-'.substr(hash('sha256', 'custody|'.$userId), 0, 8), 0, 30);
        $response = $this->client->createCustomer($name, $jwt);
        $result = isset($response['result']) && is_array($response['result']) ? $response['result'] : $response;
        $providerId = (string) ($result['id'] ?? $result['sub_partner_id'] ?? '');

        if ($providerId === '') {
            throw new \UnexpectedValueException('NOWPayments did not return a custody customer identifier.');
        }

        $customer ??= DB::table('nowpayments_customers')->create();
        $customer->userid = $userId;
        $customer->provider_subpartner_id = $providerId;
        $customer->provider_name = $name;
        $customer->status = 'active';
        $customer->metadata = json_encode(array_intersect_key($result, array_flip(['id', 'sub_partner_id', 'name'])), JSON_THROW_ON_ERROR);
        $customer->created_at = $customer->created_at ?: Helper::dtime();
        $customer->updated_at = Helper::dtime();
        $customer->save();

        return $providerId;
    }
}
