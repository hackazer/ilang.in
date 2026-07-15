<?php

declare(strict_types=1);

namespace Tests\Payments\NowPayments;

use Helpers\Payments\Nowpayments\ApiException;
use Helpers\Payments\Nowpayments\Client;
use Helpers\Payments\Nowpayments\Transport;
use Helpers\Payments\Nowpayments\TransportResponse;
use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 3);

foreach (['Transport', 'TransportResponse', 'ApiException', 'Client'] as $classFile) {
    $file = $root.'/app/helpers/payments/nowpayments/'.$classFile.'.php';

    if (is_file($file)) {
        require_once $file;
    }
}

final class ClientTest extends TestCase
{
    public function testCreatePaymentUsesApiKeyJsonAndConfiguredBaseUrl(): void
    {
        self::assertTrue(class_exists(Client::class), 'NOWPayments client is not implemented.');

        $transport = new RecordingTransport([
            new TransportResponse(201, [], '{"payment_id":"42","payment_status":"waiting"}'),
        ]);
        $client = new Client($transport, 'secret-api-key', 'https://api-sandbox.nowpayments.io/v1');

        $result = $client->createPayment([
            'price_amount' => 10,
            'price_currency' => 'usd',
            'pay_currency' => 'btc',
        ]);

        self::assertSame('42', $result['payment_id']);
        self::assertSame('POST', $transport->requests[0]['method']);
        self::assertSame('https://api-sandbox.nowpayments.io/v1/payment', $transport->requests[0]['url']);
        self::assertSame('secret-api-key', $transport->requests[0]['headers']['x-api-key']);
        self::assertSame('application/json', $transport->requests[0]['headers']['Content-Type']);
        self::assertSame(10, json_decode($transport->requests[0]['body'], true)['price_amount']);
        self::assertSame(5, $transport->requests[0]['options']['connect_timeout']);
        self::assertSame(15, $transport->requests[0]['options']['timeout']);
    }

    public function testAuthenticatedSubscriptionRequestUsesBearerAndApiKey(): void
    {
        $transport = new RecordingTransport([
            new TransportResponse(201, [], '{"id":"subscription-9"}'),
        ]);
        $client = new Client($transport, 'api-key', 'https://api.nowpayments.io/v1');

        $client->createSubscription(
            ['subscription_plan_id' => 123, 'email' => 'user@example.test'],
            'jwt-token'
        );

        self::assertSame('Bearer jwt-token', $transport->requests[0]['headers']['Authorization']);
        self::assertSame('api-key', $transport->requests[0]['headers']['x-api-key']);
        self::assertSame('https://api.nowpayments.io/v1/subscriptions', $transport->requests[0]['url']);
    }

    public function testTransientGetFailureRetriesButPostDoesNot(): void
    {
        $transport = new RecordingTransport([
            new TransportResponse(503, [], '{"message":"temporary"}'),
            new TransportResponse(200, [], '{"message":"OK"}'),
        ]);
        $client = new Client($transport, 'api-key', 'https://api.nowpayments.io/v1', 2);

        self::assertSame('OK', $client->status()['message']);
        self::assertCount(2, $transport->requests);

        $postTransport = new RecordingTransport([
            new TransportResponse(503, [], '{"message":"temporary"}'),
            new TransportResponse(201, [], '{"payment_id":"should-not-run"}'),
        ]);
        $postClient = new Client($postTransport, 'api-key', 'https://api.nowpayments.io/v1', 2);

        try {
            $postClient->createPayment(['price_amount' => 10]);
            self::fail('A failed POST request was retried or accepted.');
        } catch (ApiException $exception) {
            self::assertSame(503, $exception->statusCode());
        }

        self::assertCount(1, $postTransport->requests);
    }

    public function testErrorsNeverExposeCredentialsOrRemoteResponseBody(): void
    {
        $transport = new RecordingTransport([
            new TransportResponse(401, [], '{"message":"bad secret-api-key password-value"}'),
        ]);
        $client = new Client($transport, 'secret-api-key', 'https://api.nowpayments.io/v1');

        try {
            $client->authenticate('merchant@example.test', 'password-value');
            self::fail('Authentication failure was accepted.');
        } catch (ApiException $exception) {
            self::assertSame('NOWPayments request failed with HTTP 401.', $exception->getMessage());
            self::assertStringNotContainsString('secret-api-key', $exception->getMessage());
            self::assertStringNotContainsString('password-value', $exception->getMessage());
        }
    }
}

final class RecordingTransport implements Transport
{
    public array $requests = [];

    public function __construct(private array $responses)
    {
    }

    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        array $options
    ): TransportResponse {
        $this->requests[] = compact('method', 'url', 'headers', 'body', 'options');

        return array_shift($this->responses) ?? new TransportResponse(500, [], '');
    }
}
