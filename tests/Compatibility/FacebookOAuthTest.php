<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Helpers\FacebookOAuth;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FacebookOAuthTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testAuthorizationUrlContainsRequiredOAuthParametersAndStoresState(): void
    {
        $oauth = new FacebookOAuth('app-id', 'app-secret', 'https://example.test/facebook');

        $url = $oauth->authorizationUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('https', parse_url($url, PHP_URL_SCHEME));
        self::assertSame('www.facebook.com', parse_url($url, PHP_URL_HOST));
        self::assertSame('app-id', $query['client_id'] ?? null);
        self::assertSame('https://example.test/facebook', $query['redirect_uri'] ?? null);
        self::assertSame('code', $query['response_type'] ?? null);
        self::assertSame('email', $query['scope'] ?? null);
        self::assertSame($_SESSION[FacebookOAuth::STATE_SESSION_KEY] ?? null, $query['state'] ?? null);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $query['state'] ?? '');
    }

    public function testCodeExchangeRejectsInvalidStateBeforeCallingTransport(): void
    {
        $called = false;
        $oauth = new FacebookOAuth(
            'app-id',
            'app-secret',
            'https://example.test/facebook',
            static function () use (&$called): array {
                $called = true;
                return ['access_token' => 'token'];
            }
        );
        $_SESSION[FacebookOAuth::STATE_SESSION_KEY] = 'expected';

        try {
            $oauth->exchangeCode('code', 'wrong');
            self::fail('Invalid OAuth state was accepted.');
        } catch (RuntimeException $exception) {
            self::assertSame('Invalid Facebook OAuth state.', $exception->getMessage());
        }

        self::assertFalse($called);
    }

    public function testCodeExchangeAndProfileRequestUseExpectedGraphParameters(): void
    {
        $urls = [];
        $oauth = new FacebookOAuth(
            'app-id',
            'app-secret',
            'https://example.test/facebook',
            static function (string $url) use (&$urls): array {
                $urls[] = $url;

                return count($urls) === 1
                    ? ['access_token' => 'access-token']
                    : ['id' => '123', 'email' => 'user@example.test', 'name' => 'Test User'];
            }
        );
        $_SESSION[FacebookOAuth::STATE_SESSION_KEY] = 'valid-state';

        $token = $oauth->exchangeCode('auth-code', 'valid-state');
        $profile = $oauth->user($token);

        parse_str((string) parse_url($urls[0], PHP_URL_QUERY), $tokenQuery);
        parse_str((string) parse_url($urls[1], PHP_URL_QUERY), $profileQuery);

        self::assertSame('access-token', $token);
        self::assertSame('app-id', $tokenQuery['client_id'] ?? null);
        self::assertSame('app-secret', $tokenQuery['client_secret'] ?? null);
        self::assertSame('auth-code', $tokenQuery['code'] ?? null);
        self::assertSame('https://example.test/facebook', $tokenQuery['redirect_uri'] ?? null);
        self::assertSame('id,email,name', $profileQuery['fields'] ?? null);
        self::assertSame('access-token', $profileQuery['access_token'] ?? null);
        self::assertSame('user@example.test', $profile['email'] ?? null);
        self::assertSame('123', $profile['id'] ?? null);
        self::assertArrayNotHasKey(FacebookOAuth::STATE_SESSION_KEY, $_SESSION);
    }
}
