<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

interface PrepaidApi
{
    /** @return array<string, mixed> */
    public function createPayment(array $payload): array;
}
