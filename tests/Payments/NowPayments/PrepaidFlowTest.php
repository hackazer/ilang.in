<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\ApiException;
use Helpers\Payments\Nowpayments\PrepaidApi;
use Helpers\Payments\Nowpayments\PrepaidAttempt;
use Helpers\Payments\Nowpayments\PrepaidCommand;
use Helpers\Payments\Nowpayments\PrepaidService;
use Helpers\Payments\Nowpayments\PrepaidStore;
use PHPUnit\Framework\TestCase;

foreach (['Status.php', 'ApiException.php', 'PrepaidApi.php', 'PrepaidAttempt.php', 'PrepaidCommand.php', 'PrepaidStore.php', 'PrepaidService.php'] as $file) {
    $path = dirname(__DIR__, 3).'/app/helpers/payments/nowpayments/'.$file;

    if (is_file($path)) {
        require_once $path;
    }
}

final class PrepaidFlowTest extends TestCase
{
    public function testRepeatedCreationReturnsExistingAttemptWithoutSecondRemotePayment(): void
    {
        $store = new InMemoryPrepaidStore();
        $api = new FakePrepaidApi();
        $service = new PrepaidService($store, $api);
        $command = $this->command();

        $first = $service->create($command);
        $second = $service->create($command);

        self::assertSame($first->transactionId(), $second->transactionId());
        self::assertSame('provider-42', $second->providerPaymentId());
        self::assertSame(1, $api->calls);
    }

    public function testLocalPendingAttemptExistsBeforeRemoteRequest(): void
    {
        $store = new InMemoryPrepaidStore();
        $api = new FakePrepaidApi(static function () use ($store): void {
            self::assertCount(1, $store->attempts);
            self::assertSame('pending', array_values($store->attempts)[0]->status());
        });

        (new PrepaidService($store, $api))->create($this->command());
    }

    public function testAmbiguousTransportFailureKeepsAttemptPendingAndRethrowsSafeError(): void
    {
        $store = new InMemoryPrepaidStore();
        $api = new FakePrepaidApi(exception: new ApiException('gateway failed'));
        $service = new PrepaidService($store, $api);

        try {
            $service->create($this->command());
            self::fail('Expected remote failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('gateway failed', $exception->getMessage());
        }

        self::assertSame('pending', array_values($store->attempts)[0]->status());
    }

    public function testProviderServerFailureKeepsAttemptPending(): void
    {
        $store = new InMemoryPrepaidStore();
        $service = new PrepaidService(
            $store,
            new FakePrepaidApi(exception: new ApiException('gateway failed', 503))
        );

        try {
            $service->create($this->command());
            self::fail('Expected provider failure.');
        } catch (ApiException $exception) {
            self::assertSame(503, $exception->statusCode());
        }

        self::assertSame('pending', array_values($store->attempts)[0]->status());
    }

    public function testProviderRateLimitKeepsAttemptPending(): void
    {
        $store = new InMemoryPrepaidStore();
        $service = new PrepaidService(
            $store,
            new FakePrepaidApi(exception: new ApiException('gateway throttled', 429))
        );

        try {
            $service->create($this->command());
            self::fail('Expected provider throttling.');
        } catch (ApiException $exception) {
            self::assertSame(429, $exception->statusCode());
        }

        self::assertSame('pending', array_values($store->attempts)[0]->status());
    }

    public function testDefinitiveProviderClientFailureMarksAttemptFailed(): void
    {
        $store = new InMemoryPrepaidStore();
        $service = new PrepaidService(
            $store,
            new FakePrepaidApi(exception: new ApiException('invalid payment', 422))
        );

        try {
            $service->create($this->command());
            self::fail('Expected provider rejection.');
        } catch (ApiException $exception) {
            self::assertSame(422, $exception->statusCode());
        }

        self::assertSame('failed', array_values($store->attempts)[0]->status());
    }

    private function command(): PrepaidCommand
    {
        return new PrepaidCommand(
            userId: 5,
            planId: 9,
            term: 'monthly',
            orderId: 'np-order-1',
            idempotencyKey: 'idempotency-1',
            amount: '99.90',
            priceCurrency: 'USD',
            payCurrency: 'BTC',
            callbackUrl: 'https://example.test/webhook/nowpayments',
            description: 'Pro monthly',
            metadata: ['coupon_id' => 7]
        );
    }
}

final class FakePrepaidApi implements PrepaidApi
{
    public int $calls = 0;

    public function __construct(
        private readonly ?\Closure $before = null,
        private readonly ?\Throwable $exception = null
    )
    {
    }

    public function createPayment(array $payload): array
    {
        $this->calls++;
        ($this->before ?? static fn () => null)();

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return [
            'payment_id' => 'provider-42',
            'payment_status' => 'waiting',
            'pay_amount' => '0.001',
            'pay_currency' => 'BTC',
            'pay_address' => 'wallet-address',
        ];
    }
}

final class InMemoryPrepaidStore implements PrepaidStore
{
    /** @var array<string, PrepaidAttempt> */
    public array $attempts = [];

    public function findByIdempotencyKey(string $key): ?PrepaidAttempt
    {
        return $this->attempts[$key] ?? null;
    }

    public function createPending(PrepaidCommand $command): PrepaidAttempt
    {
        return $this->attempts[$command->idempotencyKey] = new PrepaidAttempt(
            1,
            2,
            $command->orderId,
            $command->idempotencyKey,
            'pending'
        );
    }

    public function markCreated(PrepaidAttempt $attempt, array $response): PrepaidAttempt
    {
        return $this->attempts[$attempt->idempotencyKey()] = $attempt->withProviderResponse($response);
    }

    public function markFailed(PrepaidAttempt $attempt): void
    {
        $this->attempts[$attempt->idempotencyKey()] = $attempt->withStatus('failed');
    }
}
