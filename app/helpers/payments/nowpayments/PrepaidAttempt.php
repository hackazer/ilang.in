<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class PrepaidAttempt
{
    /** @param array<string, mixed> $providerResponse */
    public function __construct(
        private readonly int $transactionId,
        private readonly int $subscriptionId,
        private readonly string $orderId,
        private readonly string $idempotencyKey,
        private readonly string $status,
        private readonly ?string $providerPaymentId = null,
        private readonly array $providerResponse = []
    ) {
    }

    public function transactionId(): int { return $this->transactionId; }
    public function subscriptionId(): int { return $this->subscriptionId; }
    public function orderId(): string { return $this->orderId; }
    public function idempotencyKey(): string { return $this->idempotencyKey; }
    public function status(): string { return $this->status; }
    public function providerPaymentId(): ?string { return $this->providerPaymentId; }

    /** @return array<string, mixed> */
    public function providerResponse(): array { return $this->providerResponse; }

    /** @param array<string, mixed> $response */
    public function withProviderResponse(array $response): self
    {
        return new self(
            $this->transactionId,
            $this->subscriptionId,
            $this->orderId,
            $this->idempotencyKey,
            Status::normalize(isset($response['payment_status']) ? (string) $response['payment_status'] : null),
            isset($response['payment_id']) ? (string) $response['payment_id'] : null,
            $response
        );
    }

    public function withStatus(string $status): self
    {
        return new self(
            $this->transactionId,
            $this->subscriptionId,
            $this->orderId,
            $this->idempotencyKey,
            $status,
            $this->providerPaymentId,
            $this->providerResponse
        );
    }
}
