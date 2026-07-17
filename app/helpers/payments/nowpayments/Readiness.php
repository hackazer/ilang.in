<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class Readiness
{
    private const CUSTODIAL_REQUIRED = [
        'api_key',
        'ipn_secret',
        'dashboard_email',
        'dashboard_password',
        'callback_url',
        'settlement_currency',
    ];

    public static function custodial(array $settings): ReadinessResult
    {
        $missing = [];

        foreach (self::CUSTODIAL_REQUIRED as $key) {
            $value = $settings[$key] ?? null;

            if (!is_scalar($value) || trim((string) $value) === '') {
                $missing[] = $key;
            }
        }

        if (!in_array('callback_url', $missing, true)) {
            $callback = (string) $settings['callback_url'];

            if (filter_var($callback, FILTER_VALIDATE_URL) === false
                || strtolower((string) parse_url($callback, PHP_URL_SCHEME)) !== 'https') {
                $missing[] = 'callback_url';
            }
        }

        return new ReadinessResult(array_values(array_unique($missing)));
    }
}
