<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class Pricing
{
    /**
     * @param array<string, mixed>|object $plan
     * @param array<string, mixed>|object|null $coupon
     * @param array<string, mixed>|object|null $tax
     */
    public static function forPlan(array|object $plan, string $term, array|object|null $coupon = null, array|object|null $tax = null): PricingResult
    {
        if (!in_array($term, ['monthly', 'yearly', 'lifetime'], true)) {
            throw new \InvalidArgumentException('Unsupported billing term.');
        }

        $plan = self::toArray($plan);
        $subtotal = self::cents($plan['price_'.$term] ?? null);

        if ($subtotal <= 0) {
            throw new \InvalidArgumentException('The selected plan has no payable price for this term.');
        }

        $couponData = $coupon === null ? [] : self::toArray($coupon);
        $discountRate = self::percentage($couponData['discount'] ?? 0);
        $discount = (int) round($subtotal * ($discountRate / 100));
        $taxable = max(0, $subtotal - $discount);

        $taxData = $tax === null ? [] : self::toArray($tax);
        $taxRate = self::percentage($taxData['rate'] ?? 0);
        $taxAmount = (int) round($taxable * ($taxRate / 100));

        return new PricingResult(
            $subtotal,
            $discount,
            $taxAmount,
            isset($couponData['id']) ? (int) $couponData['id'] : null,
            isset($taxData['id']) ? (int) $taxData['id'] : null
        );
    }

    private static function cents(mixed $value): int
    {
        if (!is_numeric($value)) {
            return 0;
        }

        return (int) round((float) $value * 100);
    }

    private static function percentage(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return min(100.0, max(0.0, (float) $value));
    }

    /** @return array<string, mixed> */
    private static function toArray(array|object $value): array
    {
        return is_array($value) ? $value : get_object_vars($value);
    }
}
