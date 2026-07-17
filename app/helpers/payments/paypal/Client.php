<?php

declare(strict_types=1);

namespace Helpers\Payments\Paypal;

use Closure;
use JsonException;

final class Client
{
    public const PRODUCTION_URL = 'https://api-m.paypal.com';
    public const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';

    private Closure $transport;
    private ?string $accessToken = null;
    private int $accessTokenExpiresAt = 0;

    public function __construct(
        callable $transport,
        private readonly string $clientId,
        private readonly string $clientSecret,
        bool $sandbox = false
    ) {
        if (trim($clientId) === '' || trim($clientSecret) === '') {
            throw new \InvalidArgumentException('PayPal credentials must not be empty.');
        }

        $this->transport = Closure::fromCallable($transport);
        $this->baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }

    private readonly string $baseUrl;

    public function authenticate(): void
    {
        $this->accessToken = null;
        $this->accessTokenExpiresAt = 0;
        $this->accessToken();
    }

    public function createOrder(array $payload): array
    {
        return $this->request('POST', '/v2/checkout/orders', $payload);
    }

    public function getOrder(string $orderId): array
    {
        return $this->request('GET', '/v2/checkout/orders/'.rawurlencode($orderId));
    }

    public function captureOrder(string $orderId): array
    {
        return $this->request('POST', '/v2/checkout/orders/'.rawurlencode($orderId).'/capture');
    }

    public function createProduct(array $payload): array
    {
        return $this->request('POST', '/v1/catalogs/products', $payload);
    }

    public function createPlan(array $payload): array
    {
        return $this->request('POST', '/v1/billing/plans', $payload);
    }

    public function createSubscription(array $payload): array
    {
        return $this->request('POST', '/v1/billing/subscriptions', $payload);
    }

    public function getSubscription(string $subscriptionId): array
    {
        return $this->request('GET', '/v1/billing/subscriptions/'.rawurlencode($subscriptionId));
    }

    public function cancelSubscription(string $subscriptionId, string $reason): array
    {
        return $this->request(
            'POST',
            '/v1/billing/subscriptions/'.rawurlencode($subscriptionId).'/cancel',
            ['reason' => $reason]
        );
    }

    public function createWebhook(string $url, array $eventNames): array
    {
        return $this->request('POST', '/v1/notifications/webhooks', [
            'url' => $url,
            'event_types' => array_map(
                static fn (string $name): array => ['name' => $name],
                array_values($eventNames)
            ),
        ]);
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        $response = $this->request('POST', '/v1/notifications/verify-webhook-signature', $payload);

        return ($response['verification_status'] ?? null) === 'SUCCESS';
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null && time() < $this->accessTokenExpiresAt) {
            return $this->accessToken;
        }

        $response = ($this->transport)(
            'POST',
            $this->baseUrl.'/v1/oauth2/token',
            [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'grant_type=client_credentials',
            $this->transportOptions()
        );
        $decoded = $this->decodeResponse($response, 'authentication');
        $token = $decoded['access_token'] ?? null;

        if (!is_string($token) || $token === '') {
            throw new ApiException('PayPal authentication response was invalid.');
        }

        $expiresIn = max(60, (int) ($decoded['expires_in'] ?? 300));
        $this->accessToken = $token;
        $this->accessTokenExpiresAt = time() + $expiresIn - 30;

        return $token;
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->accessToken(),
        ];
        $body = null;

        if ($payload !== null) {
            $headers['Content-Type'] = 'application/json';

            try {
                $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (JsonException $exception) {
                throw new \InvalidArgumentException('PayPal request payload is invalid.', 0, $exception);
            }
        }

        $response = ($this->transport)(
            strtoupper($method),
            $this->baseUrl.'/'.ltrim($path, '/'),
            $headers,
            $body,
            $this->transportOptions()
        );

        return $this->decodeResponse($response, 'API request');
    }

    private function decodeResponse(mixed $response, string $operation): array
    {
        if (!is_array($response) || !isset($response['status']) || !array_key_exists('body', $response)) {
            throw new ApiException('PayPal '.$operation.' transport response was invalid.');
        }

        $status = (int) $response['status'];
        $body = (string) $response['body'];

        if ($status < 200 || $status >= 300) {
            throw new ApiException('PayPal API request failed with HTTP '.$status.'.');
        }

        if ($body === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ApiException('PayPal '.$operation.' returned invalid JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new ApiException('PayPal '.$operation.' returned an invalid payload.');
        }

        return $decoded;
    }

    private function transportOptions(): array
    {
        return [
            'connect_timeout' => 5,
            'timeout' => 20,
            'max_response_bytes' => 2 * 1024 * 1024,
        ];
    }
}
