<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class WebhookResult
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly string $result,
        public readonly ?int $userId = null,
        public readonly ?int $planId = null,
        public readonly ?int $paymentId = null
    ) {
    }
}
