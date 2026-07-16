<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\Slack;
use PHPUnit\Framework\TestCase;

$slack = dirname(__DIR__, 2).'/app/helpers/Slack.php';

if (is_file($slack)) {
    require_once $slack;
}

final class SlackOAuthTest extends TestCase
{
    private const STATE_SESSION_KEY = 'slack_oauth_state';

    protected function setUp(): void
    {
        $_GET = [];
        unset($_SESSION[self::STATE_SESSION_KEY]);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        unset($_SESSION[self::STATE_SESSION_KEY]);
    }

    public function testAuthorizationUrlStoresAUniqueCryptographicallyRandomState(): void
    {
        self::assertTrue(method_exists(Slack::class, 'authorizationUrl'));

        $slack = new Slack('client-id', 'client-secret', 'https://example.com/slack/callback');

        $firstUrl = $slack->authorizationUrl();
        $firstParameters = $this->queryParameters($firstUrl);
        $firstState = $firstParameters['state'] ?? null;

        self::assertIsString($firstState);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $firstState);
        self::assertSame($firstState, $_SESSION[self::STATE_SESSION_KEY] ?? null);
        self::assertSame('client-id', $firstParameters['client_id'] ?? null);
        self::assertSame('https://example.com/slack/callback', $firstParameters['redirect_uri'] ?? null);
        self::assertSame('commands', $firstParameters['scope'] ?? null);

        $secondUrl = $slack->authorizationUrl();
        $secondState = $this->queryParameters($secondUrl)['state'] ?? null;

        self::assertIsString($secondState);
        self::assertNotSame($firstState, $secondState);
        self::assertSame($secondState, $_SESSION[self::STATE_SESSION_KEY] ?? null);
    }

    public function testMissingOrMismatchedStateIsRejectedAndConsumedWithoutTokenExchange(): void
    {
        $exchangeCount = 0;
        $slack = new Slack(
            'client-id',
            'client-secret',
            'https://example.com/slack/callback',
            static function () use (&$exchangeCount): object {
                $exchangeCount++;

                return (object) ['user_id' => 'U123'];
            }
        );

        $_SESSION[self::STATE_SESSION_KEY] = 'expected-state';
        $_GET = ['code' => 'oauth-code'];

        self::assertFalse($slack->process());
        self::assertArrayNotHasKey(self::STATE_SESSION_KEY, $_SESSION);
        self::assertSame(0, $exchangeCount);

        $_SESSION[self::STATE_SESSION_KEY] = 'expected-state';
        $_GET = ['code' => 'oauth-code', 'state' => 'different-state'];

        self::assertFalse($slack->process());
        self::assertArrayNotHasKey(self::STATE_SESSION_KEY, $_SESSION);
        self::assertSame(0, $exchangeCount);
    }

    public function testValidStateIsConsumedBeforeTheTokenExchange(): void
    {
        $stateWasConsumed = false;
        $slack = new Slack(
            'client-id',
            'client-secret',
            'https://example.com/slack/callback',
            static function (string $endpoint, array $data) use (&$stateWasConsumed): object {
                $stateWasConsumed = !isset($_SESSION[self::STATE_SESSION_KEY]);

                TestCase::assertSame('oauth.access', $endpoint);
                TestCase::assertSame('oauth-code', $data['code'] ?? null);

                return (object) ['user_id' => 'U123'];
            }
        );

        $_SESSION[self::STATE_SESSION_KEY] = 'expected-state';
        $_GET = ['code' => 'oauth-code', 'state' => 'expected-state'];

        self::assertSame('U123', $slack->process());
        self::assertTrue($stateWasConsumed);
        self::assertArrayNotHasKey(self::STATE_SESSION_KEY, $_SESSION);
    }

    public function testCallbackUsesConstantTimeStateVerificationAndStrictTlsPolicy(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/Slack.php');

        self::assertIsString($source);
        self::assertStringContainsString('hash_equals($expectedState, $providedState)', $source);
        self::assertStringContainsString('CURLOPT_SSL_VERIFYPEER, true', $source);
        self::assertStringContainsString('CURLOPT_SSL_VERIFYHOST, 2', $source);
        self::assertStringNotContainsString('CURLOPT_SSL_VERIFYPEER, false', $source);
    }

    private function queryParameters(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);

        self::assertIsString($query);

        parse_str($query, $parameters);

        return $parameters;
    }
}
