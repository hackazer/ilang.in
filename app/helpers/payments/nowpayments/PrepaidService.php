<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class PrepaidService
{
    public function __construct(private readonly PrepaidStore $store, private readonly PrepaidApi $api)
    {
    }

    public function create(PrepaidCommand $command): PrepaidAttempt
    {
        if ($existing = $this->store->findByIdempotencyKey($command->idempotencyKey)) {
            return $existing;
        }

        $attempt = $this->store->createPending($command);

        try {
            $response = $this->api->createPayment($command->providerPayload());

            if (!isset($response['payment_id']) || trim((string) $response['payment_id']) === '') {
                throw new \UnexpectedValueException('NOWPayments did not return a payment identifier.');
            }

            return $this->store->markCreated($attempt, $response);
        } catch (\Throwable $exception) {
            if ($exception instanceof ApiException && $exception->isDefinitiveClientFailure()) {
                $this->store->markFailed($attempt);
            }

            throw $exception;
        }
    }
}
