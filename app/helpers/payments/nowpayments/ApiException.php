<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use RuntimeException;

final class ApiException extends RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 0)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
