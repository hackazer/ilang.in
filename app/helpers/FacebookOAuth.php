<?php

declare(strict_types=1);

namespace Helpers;

use Core\Http;
use RuntimeException;

final class FacebookOAuth
{
    public const STATE_SESSION_KEY = 'facebook_oauth_state';

    private const GRAPH_VERSION = 'v24.0';
    private const AUTHORIZATION_ENDPOINT = 'https://www.facebook.com/'.self::GRAPH_VERSION.'/dialog/oauth';
    private const GRAPH_ENDPOINT = 'https://graph.facebook.com/'.self::GRAPH_VERSION;

    private string $appId;
    private string $appSecret;
    private string $redirectUri;
    private $transport;

    public function __construct(
        string $appId,
        string $appSecret,
        string $redirectUri,
        ?callable $transport = null
    ) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->redirectUri = $redirectUri;
        $this->transport = $transport;
    }

    public function authorizationUrl(): string
    {
        $state = bin2hex(random_bytes(32));
        $_SESSION[self::STATE_SESSION_KEY] = $state;

        return self::AUTHORIZATION_ENDPOINT.'?'.$this->query([
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email',
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code, string $state): string
    {
        $expectedState = $_SESSION[self::STATE_SESSION_KEY] ?? '';
        unset($_SESSION[self::STATE_SESSION_KEY]);

        if (!is_string($expectedState)
            || $expectedState === ''
            || $state === ''
            || !hash_equals($expectedState, $state)) {
            throw new RuntimeException('Invalid Facebook OAuth state.');
        }

        $response = $this->requestJson(self::GRAPH_ENDPOINT.'/oauth/access_token?'.$this->query([
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]));

        $accessToken = $response['access_token'] ?? null;

        if (!is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Facebook did not return an access token.');
        }

        return $accessToken;
    }

    public function user(string $accessToken): array
    {
        return $this->requestJson(self::GRAPH_ENDPOINT.'/me?'.$this->query([
            'access_token' => $accessToken,
            'fields' => 'id,email,name',
        ]));
    }

    private function query(array $parameters): string
    {
        return http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    private function requestJson(string $url): array
    {
        if ($this->transport !== null) {
            $response = ($this->transport)($url);
        } else {
            $http = Http::url($url)->get(['timeout' => 10]);
            $status = $http->httpCode();
            $body = $http->getBody();

            if ($status === false || $body === false || $status >= 400) {
                throw new RuntimeException('Facebook OAuth request failed.');
            }

            $response = json_decode($body, true);
        }

        if (!is_array($response)) {
            throw new RuntimeException('Facebook returned an invalid OAuth response.');
        }

        if (isset($response['error'])) {
            $message = is_array($response['error']) && is_string($response['error']['message'] ?? null)
                ? $response['error']['message']
                : 'Facebook OAuth request failed.';

            throw new RuntimeException($message);
        }

        return $response;
    }
}
