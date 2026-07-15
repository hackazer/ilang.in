<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class TransportResponse
{
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body
    ) {
    }
}
