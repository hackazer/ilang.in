<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class Order
{
    private function __construct(
        private readonly string $id,
        private readonly string $idempotencyKey,
        private readonly string $attemptId
    ) {
    }

    public static function create(int $userId, int $planId, string $term, string $mode): self
    {
        return self::fromAttempt($userId, $planId, $term, $mode, bin2hex(random_bytes(16)));
    }

    public static function fromAttempt(
        int $userId,
        int $planId,
        string $term,
        string $mode,
        string $attemptId
    ): self {
        $parts = [
            'user' => max(0, $userId),
            'plan' => max(0, $planId),
            'term' => self::slug($term),
            'mode' => self::slug($mode),
            'attempt' => self::slug($attemptId),
        ];
        $canonical = implode('|', $parts);
        $digest = hash('sha256', $canonical);
        $prefix = sprintf('np-u%d-p%d-%s-%s-', $parts['user'], $parts['plan'], $parts['term'], $parts['mode']);
        $id = substr($prefix, 0, 51).substr($digest, 0, 12);

        return new self(rtrim($id, '-'), $digest, $attemptId);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function attemptId(): string
    {
        return $this->attemptId;
    }

    private static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-') ?: 'unknown';
    }
}
