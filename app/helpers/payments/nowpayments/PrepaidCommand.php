<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class PrepaidCommand
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public readonly int $userId,
        public readonly int $planId,
        public readonly string $term,
        public readonly string $orderId,
        public readonly string $idempotencyKey,
        public readonly string $amount,
        public readonly string $priceCurrency,
        public readonly string $payCurrency,
        public readonly string $callbackUrl,
        public readonly string $description,
        public readonly array $metadata = []
    ) {
        if ($userId <= 0 || $planId <= 0 || !in_array($term, ['monthly', 'yearly', 'lifetime'], true)) {
            throw new \InvalidArgumentException('Invalid prepaid payment context.');
        }

        if ($orderId === '' || $idempotencyKey === '' || (float) $amount <= 0) {
            throw new \InvalidArgumentException('Invalid prepaid payment identity or amount.');
        }

        if (!preg_match('/^[A-Z0-9_-]{2,16}$/i', $priceCurrency)
            || !preg_match('/^[A-Z0-9_-]{2,32}$/i', $payCurrency)) {
            throw new \InvalidArgumentException('Invalid prepaid payment currency.');
        }

        if (filter_var($callbackUrl, FILTER_VALIDATE_URL) === false
            || strtolower((string) parse_url($callbackUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new \InvalidArgumentException('NOWPayments callback URL must use HTTPS.');
        }
    }

    /** @return array<string, mixed> */
    public function providerPayload(): array
    {
        return [
            'price_amount' => $this->amount,
            'price_currency' => strtolower($this->priceCurrency),
            'pay_currency' => strtolower($this->payCurrency),
            'ipn_callback_url' => $this->callbackUrl,
            'order_id' => $this->orderId,
            'order_description' => substr($this->description, 0, 255),
        ];
    }
}
