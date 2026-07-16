<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class RecurringCycle
{
    public static function key(array $payload, string $subscriptionId): ?string
    {
        $subscriptionId = trim($subscriptionId);

        if ($subscriptionId === '') {
            return null;
        }

        $paymentId = trim((string) ($payload['payment_id'] ?? ''));

        if ($paymentId !== '') {
            return hash('sha256', 'subscription:'.$subscriptionId.'|payment:'.$paymentId);
        }

        foreach (['expire_date', 'created_at'] as $field) {
            $value = trim((string) ($payload[$field] ?? ''));
            $timestamp = $value === '' ? false : strtotime($value);

            if ($timestamp !== false) {
                return hash('sha256', 'subscription:'.$subscriptionId.'|period:'.gmdate('Y-m-d H:i:s', $timestamp));
            }
        }

        return null;
    }

    public static function orderId(string $cycleKey): string
    {
        return 'np-cycle-'.substr(hash('sha256', 'order|'.$cycleKey), 0, 48);
    }

    public static function idempotencyKey(string $cycleKey): string
    {
        return hash('sha256', 'nowpayments|recurring-cycle|'.$cycleKey);
    }
}
