<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class ReadinessResult
{
    public function __construct(private readonly array $missing)
    {
    }

    public function ready(): bool
    {
        return $this->missing === [];
    }

    public function missing(): array
    {
        return $this->missing;
    }
}
