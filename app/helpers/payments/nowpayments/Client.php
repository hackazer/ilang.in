<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use JsonException;

final class Client implements PrepaidApi
{
    public const PRODUCTION_URL = 'https://api.nowpayments.io/v1';
    public const SANDBOX_URL = 'https://api-sandbox.nowpayments.io/v1';

    public function __construct(
        private readonly Transport $transport,
        private readonly string $apiKey,
        private readonly string $baseUrl = self::PRODUCTION_URL,
        private readonly int $maxGetAttempts = 2
    ) {
        if (strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new \InvalidArgumentException('NOWPayments API URL must use HTTPS.');
        }
    }

    public function status(): array
    {
        return $this->request('GET', '/status', useApiKey: false);
    }

    public function currencies(bool $fixedRate = true): array
    {
        return $this->request('GET', '/currencies', ['fixed_rate' => $fixedRate ? 'true' : 'false']);
    }

    public function fullCurrencies(): array
    {
        return $this->request('GET', '/full-currencies');
    }

    public function checkedCurrencies(): array
    {
        return $this->request('GET', '/merchant/coins');
    }

    public function minimumAmount(array $query): array
    {
        return $this->request('GET', '/min-amount', $query);
    }

    public function estimate(array $query): array
    {
        return $this->request('GET', '/estimate', $query);
    }

    public function createPayment(array $payload): array
    {
        return $this->request('POST', '/payment', payload: $payload);
    }

    public function payment(string|int $paymentId): array
    {
        return $this->request('GET', '/payment/'.rawurlencode((string) $paymentId));
    }

    public function payments(array $query = [], ?string $jwt = null): array
    {
        return $this->request('GET', '/payment', $query, jwt: $jwt);
    }

    public function createInvoice(array $payload): array
    {
        return $this->request('POST', '/invoice', payload: $payload);
    }

    public function authenticate(string $email, string $password): array
    {
        return $this->request(
            'POST',
            '/auth',
            payload: ['email' => $email, 'password' => $password],
            useApiKey: false
        );
    }

    public function createPlan(array $payload, string $jwt): array
    {
        return $this->request('POST', '/subscriptions/plans', payload: $payload, jwt: $jwt, useApiKey: false);
    }

    public function updatePlan(string|int $planId, array $payload, string $jwt): array
    {
        return $this->request(
            'PATCH',
            '/subscriptions/plans/'.rawurlencode((string) $planId),
            payload: $payload,
            jwt: $jwt,
            useApiKey: false
        );
    }

    public function plan(string|int $planId, string $jwt): array
    {
        return $this->request(
            'GET',
            '/subscriptions/plans/'.rawurlencode((string) $planId),
            jwt: $jwt,
            useApiKey: false
        );
    }

    public function plans(string $jwt, array $query = []): array
    {
        return $this->request('GET', '/subscriptions/plans', $query, jwt: $jwt, useApiKey: false);
    }

    public function createSubscription(array $payload, string $jwt): array
    {
        return $this->request('POST', '/subscriptions', payload: $payload, jwt: $jwt);
    }

    public function subscription(string|int $subscriptionId, string $jwt): array
    {
        return $this->request(
            'GET',
            '/subscriptions/'.rawurlencode((string) $subscriptionId),
            jwt: $jwt,
            useApiKey: false
        );
    }

    public function subscriptions(string $jwt, array $query = []): array
    {
        return $this->request('GET', '/subscriptions', $query, jwt: $jwt, useApiKey: false);
    }

    public function cancelSubscription(string|int $subscriptionId, string $jwt): array
    {
        return $this->request(
            'DELETE',
            '/subscriptions/'.rawurlencode((string) $subscriptionId),
            jwt: $jwt,
            useApiKey: false
        );
    }

    public function createCustomer(string $name, string $jwt): array
    {
        return $this->request(
            'POST',
            '/sub-partner/balance',
            payload: ['name' => $name],
            jwt: $jwt,
            useApiKey: false
        );
    }

    public function customerBalance(string|int $customerId, string $jwt): array
    {
        return $this->request(
            'GET',
            '/sub-partner/balance/'.rawurlencode((string) $customerId),
            jwt: $jwt
        );
    }

    public function createCustomerDeposit(array $payload, string $jwt): array
    {
        return $this->request('POST', '/sub-partner/payment', payload: $payload, jwt: $jwt);
    }

    private function request(
        string $method,
        string $path,
        array $query = [],
        ?array $payload = null,
        ?string $jwt = null,
        bool $useApiKey = true
    ): array {
        $method = strtoupper($method);
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
        $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '');

        if ($query !== []) {
            $url .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = ['Accept' => 'application/json'];

        if ($useApiKey && trim($this->apiKey) !== '') {
            $headers['x-api-key'] = trim($this->apiKey);
        }

        if ($jwt !== null && trim($jwt) !== '') {
            $headers['Authorization'] = 'Bearer '.trim($jwt);
        }

        $body = null;

        if ($payload !== null) {
            $headers['Content-Type'] = 'application/json';

            try {
                $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (JsonException $exception) {
                throw new \InvalidArgumentException('NOWPayments request payload is invalid.', 0, $exception);
            }
        }

        $attempts = $method === 'GET' ? max(1, $this->maxGetAttempts) : 1;
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $response = $this->transport->request($method, $url, $headers, $body, [
                'connect_timeout' => 5,
                'timeout' => 15,
                'max_response_bytes' => 2 * 1024 * 1024,
            ]);

            if ($response->status < 500 && $response->status !== 429) {
                break;
            }
        }

        if (!$response instanceof TransportResponse || $response->status < 200 || $response->status >= 300) {
            $status = $response instanceof TransportResponse ? $response->status : 0;
            throw new ApiException('NOWPayments request failed with HTTP '.$status.'.', $status);
        }

        if (trim($response->body) === '') {
            return [];
        }

        try {
            $decoded = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ApiException('NOWPayments returned an invalid JSON response.', $response->status);
        }

        if (!is_array($decoded)) {
            throw new ApiException('NOWPayments returned an unexpected response.', $response->status);
        }

        return $decoded;
    }
}
