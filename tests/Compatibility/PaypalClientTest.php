<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Helpers\Payments\Paypal\ApiException;
use Helpers\Payments\Paypal\Client;
use PHPUnit\Framework\TestCase;

final class PaypalClientTest extends TestCase
{
    public function testCreatesAndCapturesAnOrderUsingOauth(): void
    {
        $requests = [];
        $client = $this->client([
            $this->jsonResponse(['access_token' => 'access-token', 'expires_in' => 3600]),
            $this->jsonResponse(['id' => 'ORDER-1', 'status' => 'CREATED']),
            $this->jsonResponse(['id' => 'ORDER-1', 'status' => 'COMPLETED']),
        ], $requests);

        $order = $client->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [['amount' => ['currency_code' => 'USD', 'value' => '12.50']]],
        ]);
        $capture = $client->captureOrder('ORDER-1');

        self::assertSame('ORDER-1', $order['id']);
        self::assertSame('COMPLETED', $capture['status']);
        self::assertSame('POST', $requests[0]['method']);
        self::assertSame('https://api-m.sandbox.paypal.com/v1/oauth2/token', $requests[0]['url']);
        self::assertSame('Basic '.base64_encode('client-id:client-secret'), $requests[0]['headers']['Authorization']);
        self::assertSame('grant_type=client_credentials', $requests[0]['body']);
        self::assertSame('https://api-m.sandbox.paypal.com/v2/checkout/orders', $requests[1]['url']);
        self::assertSame('Bearer access-token', $requests[1]['headers']['Authorization']);
        self::assertSame('12.50', json_decode($requests[1]['body'], true, 512, JSON_THROW_ON_ERROR)['purchase_units'][0]['amount']['value']);
        self::assertSame('https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER-1/capture', $requests[2]['url']);
        self::assertNull($requests[2]['body']);
    }

    public function testSupportsProductsPlansSubscriptionsWebhooksAndCancellation(): void
    {
        $requests = [];
        $client = $this->client([
            $this->jsonResponse(['access_token' => 'token', 'expires_in' => 3600]),
            $this->jsonResponse(['id' => 'PRODUCT-1']),
            $this->jsonResponse(['id' => 'PLAN-1', 'status' => 'ACTIVE']),
            $this->jsonResponse(['id' => 'SUB-1', 'status' => 'APPROVAL_PENDING']),
            $this->jsonResponse(['id' => 'SUB-1', 'status' => 'ACTIVE']),
            ['status' => 204, 'headers' => [], 'body' => ''],
            $this->jsonResponse(['id' => 'WEBHOOK-1']),
            $this->jsonResponse(['verification_status' => 'SUCCESS']),
        ], $requests);

        self::assertSame('PRODUCT-1', $client->createProduct(['name' => 'Pro'])['id']);
        self::assertSame('PLAN-1', $client->createPlan(['product_id' => 'PRODUCT-1'])['id']);
        self::assertSame('SUB-1', $client->createSubscription(['plan_id' => 'PLAN-1'])['id']);
        self::assertSame('ACTIVE', $client->getSubscription('SUB-1')['status']);
        self::assertSame([], $client->cancelSubscription('SUB-1', 'Customer request'));
        self::assertSame('WEBHOOK-1', $client->createWebhook('https://example.test/webhook', ['BILLING.SUBSCRIPTION.ACTIVATED'])['id']);
        self::assertTrue($client->verifyWebhookSignature([
            'auth_algo' => 'SHA256withRSA',
            'cert_url' => 'https://api.paypal.com/cert.pem',
            'transmission_id' => 'transmission-id',
            'transmission_sig' => 'signature',
            'transmission_time' => '2026-07-16T00:00:00Z',
            'webhook_id' => 'WEBHOOK-1',
            'webhook_event' => ['id' => 'EVENT-1'],
        ]));

        $urls = array_column($requests, 'url');
        self::assertContains('https://api-m.sandbox.paypal.com/v1/catalogs/products', $urls);
        self::assertContains('https://api-m.sandbox.paypal.com/v1/billing/plans', $urls);
        self::assertContains('https://api-m.sandbox.paypal.com/v1/billing/subscriptions', $urls);
        self::assertContains('https://api-m.sandbox.paypal.com/v1/billing/subscriptions/SUB-1', $urls);
        self::assertContains('https://api-m.sandbox.paypal.com/v1/notifications/webhooks', $urls);
        self::assertContains('https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature', $urls);
        self::assertSame('Customer request', json_decode($requests[5]['body'], true, 512, JSON_THROW_ON_ERROR)['reason']);
    }

    public function testRejectsPaypalApiErrorsWithoutLeakingResponseDetails(): void
    {
        $requests = [];
        $client = $this->client([
            $this->jsonResponse(['access_token' => 'token', 'expires_in' => 3600]),
            $this->jsonResponse(['name' => 'INVALID_REQUEST', 'message' => 'Sensitive upstream detail'], 422),
        ], $requests);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('PayPal API request failed with HTTP 422.');

        $client->createProduct(['name' => 'Pro']);
    }

    private function client(array $responses, array &$requests): Client
    {
        $transport = static function (
            string $method,
            string $url,
            array $headers,
            ?string $body,
            array $options
        ) use (&$responses, &$requests): array {
            $requests[] = compact('method', 'url', 'headers', 'body', 'options');
            $response = array_shift($responses);
            self::assertIsArray($response, 'Unexpected PayPal transport request.');

            return $response;
        };

        return new Client($transport, 'client-id', 'client-secret', true);
    }

    private function jsonResponse(array $payload, int $status = 200): array
    {
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload, JSON_THROW_ON_ERROR),
        ];
    }
}
