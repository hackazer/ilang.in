<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class EntitlementService
{
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

    /** @return array{user_id:int, plan_id:int, payment_id:int} */
    public function apply(object $transaction, array $payload): array
    {
        if ($transaction->entitlement_applied_at) {
            return [
                'user_id' => (int) $transaction->userid,
                'plan_id' => (int) $transaction->planid,
                'payment_id' => (int) $transaction->paymentid,
            ];
        }

        $user = DB::user()->where('id', $transaction->userid)->first();
        $subscription = DB::subscription()->where('id', $transaction->subscriptionid)->first();

        if (!$user || !$subscription) {
            throw new \RuntimeException('NOWPayments local billing context is missing.');
        }

        $expiry = self::expiry((string) $transaction->term, $user->expiration ? (string) $user->expiration : null);
        $providerId = (string) $transaction->provider_payment_id;

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

        $subscription->tid = $providerId;
        $subscription->status = 'Active';
        $subscription->amount = $transaction->expected_amount;
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
}
