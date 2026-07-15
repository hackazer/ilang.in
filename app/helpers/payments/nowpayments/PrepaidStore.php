<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

interface PrepaidStore
{
    public function findByIdempotencyKey(string $key): ?PrepaidAttempt;
    public function createPending(PrepaidCommand $command): PrepaidAttempt;

    /** @param array<string, mixed> $response */
    public function markCreated(PrepaidAttempt $attempt, array $response): PrepaidAttempt;

    public function markFailed(PrepaidAttempt $attempt): void;
}
