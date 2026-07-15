<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

interface Transport
{
    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        array $options
    ): TransportResponse;
}
