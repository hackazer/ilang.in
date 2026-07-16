<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class EntitlementService
{
    public static function shouldApply(string $mode, string $status): bool
    {
        return $status === Status::PAID && $mode !== 'custodial_deposit';
    }

    public static function expiry(string $term, ?string $currentExpiration, ?int $now = null): string
    {
        $now ??= time();
        $current = $currentExpiration !== null ? strtotime($currentExpiration) : false;
        $base = max($now, $current === false ? 0 : $current);
        $modifier = match ($term) {
            'yearly' => '+1 year',
            'lifetime' => '+20 years',
            default => '+1 month',
        };

        return date('Y-m-d H:i:s', strtotime($modifier, $base));
    }

    public static function decimalEquals(mixed $left, mixed $right): bool
    {
        $left = self::normalizeDecimal($left);
        $right = self::normalizeDecimal($right);

        return $left !== null && $right !== null && $left === $right;
    }

    public static function normalizeDecimal(mixed $value): ?string
    {
        if (is_int($value)) {
            $value = (string) $value;
        } elseif (is_float($value)) {
            if (!is_finite($value)) {
                return null;
            }

            $value = (string) $value;
        } elseif (is_string($value)) {
            $value = trim($value);
        } else {
            return null;
        }

        if (!preg_match('/^([+-]?)(\d+)(?:\.(\d*))?(?:[eE]([+-]?\d+))?$/', $value, $matches)) {
            return null;
        }

        $sign = $matches[1] === '-' ? '-' : '';
        $integer = $matches[2];
        $fraction = $matches[3] ?? '';
        $exponent = isset($matches[4]) ? (int) $matches[4] : 0;
        $digits = $integer.$fraction;
        $point = strlen($integer) + $exponent;

        if ($point <= 0) {
            $integer = '0';
            $fraction = str_repeat('0', -$point).$digits;
        } elseif ($point >= strlen($digits)) {
            $integer = $digits.str_repeat('0', $point - strlen($digits));
            $fraction = '';
        } else {
            $integer = substr($digits, 0, $point);
            $fraction = substr($digits, $point);
        }

        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = rtrim($fraction, '0');

        if ($integer === '0' && $fraction === '') {
            return '0';
        }

        return $sign.$integer.($fraction === '' ? '' : '.'.$fraction);
    }

    public static function nextSubscriptionAmount(mixed $current, string $status, mixed $paid): string
    {
        $paid = self::normalizeDecimal($paid);

        if ($paid === null || str_starts_with($paid, '-')) {
            throw new \InvalidArgumentException('Paid amount must be a non-negative decimal.');
        }

        if (strcasecmp($status, 'Pending') === 0) {
            return $paid;
        }

        return self::addDecimals($current, $paid);
    }

    /** @return array{user_id:int, plan_id:int, payment_id:int}|null */
    public function apply(object $transaction, array $payload): ?array
    {
        if ($transaction->entitlement_applied_at) {
            return null;
        }

        $user = DB::user()->where('id', $transaction->userid)->first();
        $subscription = DB::subscription()->where('id', $transaction->subscriptionid)->first();

        if (!$user || !$subscription) {
            throw new \RuntimeException('NOWPayments local billing context is missing.');
        }

        $expiry = self::expiry((string) $transaction->term, $user->expiration ? (string) $user->expiration : null);
        $providerId = (string) ($transaction->provider_payment_id ?: $transaction->provider_subscription_id);

        $payment = DB::payment()->where('tid', $providerId)->first();

        if (!$payment) {
            $payment = DB::payment()->create();
            $payment->date = Helper::dtime();
            $payment->tid = $providerId;
            $payment->amount = $transaction->expected_amount;
            $payment->userid = $transaction->userid;
            $payment->status = 'Completed';
            $payment->expiry = $expiry;
            $payment->data = json_encode([
                'paymentmethod' => 'nowpayments',
                'mode' => $transaction->mode,
                'order_id' => $transaction->order_id,
                'pay_currency' => $transaction->pay_currency,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $payment->save();
        }

        $subscriptionAmount = self::nextSubscriptionAmount(
            $subscription->amount ?? '0',
            (string) $subscription->status,
            $transaction->expected_amount
        );
        $subscription->tid = $providerId;
        $subscription->status = 'Active';
        $subscription->amount = $subscriptionAmount;
        $subscription->expiry = $expiry;
        $subscription->lastpayment = Helper::dtime();
        $subscription->data = json_encode([
            'type' => 'nowpayments',
            'paymentmethod' => 'nowpayments',
            'mode' => $transaction->mode,
            'provider_subscription_id' => $transaction->provider_subscription_id,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $subscription->save();

        $user->last_payment = Helper::dtime();
        $user->expiration = $expiry;
        $user->trial = 0;
        $user->pro = 1;
        $user->planid = $transaction->planid;
        $user->save();

        $metadata = json_decode((string) $transaction->metadata, true);

        if (is_array($metadata) && !empty($metadata['coupon_id'])) {
            if ($coupon = DB::coupons()->where('id', (int) $metadata['coupon_id'])->first()) {
                $coupon->used = (int) $coupon->used + 1;
                $coupon->save();
            }
        }

        $transaction->paymentid = $payment->id();
        $transaction->entitlement_applied_at = Helper::dtime();
        $transaction->save();

        return [
            'user_id' => (int) $user->id,
            'plan_id' => (int) $transaction->planid,
            'payment_id' => (int) $payment->id(),
        ];
    }

    private static function addDecimals(mixed $left, mixed $right): string
    {
        $left = self::normalizeDecimal($left);
        $right = self::normalizeDecimal($right);

        if ($left === null || $right === null || str_starts_with($left, '-') || str_starts_with($right, '-')) {
            throw new \InvalidArgumentException('Monetary totals must be non-negative decimals.');
        }

        [$leftInteger, $leftFraction] = array_pad(explode('.', $left, 2), 2, '');
        [$rightInteger, $rightFraction] = array_pad(explode('.', $right, 2), 2, '');
        $scale = max(strlen($leftFraction), strlen($rightFraction));
        $integerWidth = max(strlen($leftInteger), strlen($rightInteger));
        $leftDigits = str_pad($leftInteger, $integerWidth, '0', STR_PAD_LEFT).str_pad($leftFraction, $scale, '0');
        $rightDigits = str_pad($rightInteger, $integerWidth, '0', STR_PAD_LEFT).str_pad($rightFraction, $scale, '0');
        $carry = 0;
        $sum = '';

        for ($index = strlen($leftDigits) - 1; $index >= 0; $index--) {
            $digit = (int) $leftDigits[$index] + (int) $rightDigits[$index] + $carry;
            $sum = (string) ($digit % 10).$sum;
            $carry = intdiv($digit, 10);
        }

        if ($carry !== 0) {
            $sum = (string) $carry.$sum;
        }

        if ($scale !== 0) {
            $sum = substr($sum, 0, -$scale).'.'.substr($sum, -$scale);
        }

        return self::normalizeDecimal($sum) ?? '0';
    }
}
