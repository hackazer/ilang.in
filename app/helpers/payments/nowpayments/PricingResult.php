<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class PricingResult
{
    public function __construct(
        private readonly int $subtotalCents,
        private readonly int $discountCents,
        private readonly int $taxCents,
        private readonly ?int $couponId,
        private readonly ?int $taxId
    ) {
    }

    public function subtotal(): string
    {
        return self::formatDecimal($this->subtotalCents);
    }

    public function discount(): string
    {
        return self::formatDecimal($this->discountCents);
    }

    public function tax(): string
    {
        return self::formatDecimal($this->taxCents);
    }

    public function decimal(): string
    {
        return self::formatDecimal($this->subtotalCents - $this->discountCents + $this->taxCents);
    }

    public function couponId(): ?int
    {
        return $this->couponId;
    }

    public function taxId(): ?int
    {
        return $this->taxId;
    }

    /** @return array<string, int|string|null> */
    public function metadata(): array
    {
        return [
            'subtotal' => $this->subtotal(),
            'discount' => $this->discount(),
            'tax' => $this->tax(),
            'total' => $this->decimal(),
            'coupon_id' => $this->couponId,
            'tax_id' => $this->taxId,
        ];
    }

    private static function formatDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
