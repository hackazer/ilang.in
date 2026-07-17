<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Helper;
use Helpers\AuthThrottle;
use Helpers\PasswordPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthenticationHardeningTest extends TestCase
{
    private string $root;
    private string $fallbackDirectory;
    private array $session;
    private array $cookies;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
        $this->fallbackDirectory = sys_get_temp_dir().'/ilang-auth-throttle-test-'.bin2hex(random_bytes(8));
        $this->session = $_SESSION ?? [];
        $this->cookies = $_COOKIE;

        $_SESSION = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->session;
        $_COOKIE = $this->cookies;

        $this->removeDirectory($this->fallbackDirectory);
    }

    public function testThrottleKeyNormalizesIdentityAndIpWithoutLeakingIdentity(): void
    {
        $throttle = $this->throttle();

        $normalized = $throttle->key(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '192.0.2.25');
        $variant = $throttle->key(AuthThrottle::LOGIN_SCOPE, '  USER@Example.COM  ', '::ffff:192.0.2.25');

        self::assertSame($normalized, $variant);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $normalized);
        self::assertStringNotContainsString('user@example.com', $normalized);
        self::assertNotSame(
            $normalized,
            $throttle->key(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '192.0.2.26'),
        );
        self::assertNotSame(
            $normalized,
            $throttle->key(AuthThrottle::TWO_FACTOR_SCOPE, 'user@example.com', '192.0.2.25'),
        );
    }

    public function testLoginFailuresPersistWhenAttackersRotateSessionsAndCookies(): void
    {
        $firstRequest = $this->throttle(maxAttempts: 3);

        $_SESSION = ['login_count' => 0];
        $_COOKIE = ['__bl' => 'attacker-controlled'];
        $firstRequest->recordFailure(AuthThrottle::LOGIN_SCOPE, 'victim@example.com', '203.0.113.25');

        $_SESSION = ['login_count' => 0, 'rotated' => true];
        $_COOKIE = [];
        $secondRequest = $this->throttle(maxAttempts: 3);
        $secondRequest->recordFailure(AuthThrottle::LOGIN_SCOPE, 'VICTIM@example.com', '203.0.113.25');
        $secondRequest->recordFailure(AuthThrottle::LOGIN_SCOPE, ' victim@example.com ', '203.0.113.25');

        self::assertTrue(
            $firstRequest->isBlocked(AuthThrottle::LOGIN_SCOPE, 'victim@example.com', '203.0.113.25'),
        );
    }

    public function testTwoFactorFailuresHaveAnIndependentEnforcedBucket(): void
    {
        $throttle = $this->throttle(maxAttempts: 2);

        $throttle->recordFailure(AuthThrottle::TWO_FACTOR_SCOPE, '42', '198.51.100.10');
        $throttle->recordFailure(AuthThrottle::TWO_FACTOR_SCOPE, '42', '198.51.100.10');

        self::assertTrue($throttle->isBlocked(AuthThrottle::TWO_FACTOR_SCOPE, '42', '198.51.100.10'));
        self::assertFalse($throttle->isBlocked(AuthThrottle::LOGIN_SCOPE, '42', '198.51.100.10'));
    }

    public function testSuccessfulAuthenticationClearsTheFailureBucket(): void
    {
        $throttle = $this->throttle(maxAttempts: 2);

        $throttle->recordFailure(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '203.0.113.50');
        $throttle->recordFailure(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '203.0.113.50');
        self::assertTrue($throttle->isBlocked(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '203.0.113.50'));

        $throttle->clear(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '203.0.113.50');

        self::assertFalse($throttle->isBlocked(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '203.0.113.50'));
    }

    public function testConfiguredCacheStoresTheCounterWithAStableExpiry(): void
    {
        $cache = new AuthThrottleCachePool();
        $throttle = $this->throttle(maxAttempts: 3, cache: $cache, cacheEnabled: true);

        self::assertSame(1, $throttle->recordFailure(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '192.0.2.10'));
        self::assertSame(2, $throttle->recordFailure(AuthThrottle::LOGIN_SCOPE, 'user@example.com', '192.0.2.10'));

        $item = array_values($cache->items)[0];
        self::assertSame(1_700_003_600, $item->expiresAt?->getTimestamp());
        self::assertSame(1, $item->incrementCalls);
        self::assertSame(2, $item->value);
    }

    public function testLoginAndTwoFactorControllersUseTheServerSideThrottle(): void
    {
        $controller = $this->read('app/controllers/UsersController.php');
        $login = $this->methodSource($controller, 'loginAuth');
        $twoFactor = $this->methodSource($controller, 'login2FAValidate');

        self::assertStringNotContainsString("session('login_count'", $login);
        self::assertStringNotContainsString("cookie('__bl'", $login);
        self::assertStringContainsString('AuthThrottle::LOGIN_SCOPE', $login);
        self::assertStringContainsString('->isBlocked(', $login);
        self::assertStringContainsString('->recordFailure(', $login);
        self::assertStringContainsString('->clear(', $login);

        self::assertStringContainsString('AuthThrottle::TWO_FACTOR_SCOPE', $twoFactor);
        self::assertStringContainsString('->isBlocked(', $twoFactor);
        self::assertGreaterThanOrEqual(2, substr_count($twoFactor, '->recordFailure('));
        self::assertStringContainsString('->clear(', $twoFactor);
    }

    #[DataProvider('newPasswordCallSiteProvider')]
    public function testEveryOwnedNewPasswordCallSiteUsesTheTwelveCharacterPolicy(
        string $path,
        string $method,
    ): void {
        $source = $this->methodSource($this->read($path), $method);

        self::assertStringContainsString('PasswordPolicy::allows(', $source, $path.'::'.$method);
        self::assertStringNotContainsString('Password must be at least 5 characters.', $source, $path.'::'.$method);
        self::assertStringNotContainsString('Password must contain at least 5 characters.', $source, $path.'::'.$method);
    }

    public static function newPasswordCallSiteProvider(): iterable
    {
        yield 'public registration' => ['app/controllers/UsersController.php', 'registerValidate'];
        yield 'password reset' => ['app/controllers/UsersController.php', 'resetChange'];
        yield 'invitation acceptance' => ['app/controllers/UsersController.php', 'acceptInvitation'];
        yield 'admin user creation' => ['app/controllers/admin/UsersController.php', 'save'];
        yield 'admin password reset' => ['app/controllers/admin/UsersController.php', 'update'];
        yield 'API user creation' => ['app/controllers/api/UsersController.php', 'create'];
        yield 'API account password update' => ['app/controllers/api/AccountController.php', 'update'];
        yield 'self-service password update' => ['app/controllers/user/AccountController.php', 'settingsUpdate'];
    }

    public function testPasswordPolicyRequiresTwelveCharacters(): void
    {
        $this->requireHelper('PasswordPolicy');

        self::assertFalse(PasswordPolicy::allows(str_repeat('a', 11)));
        self::assertTrue(PasswordPolicy::allows(str_repeat('a', 12)));
        self::assertFalse(PasswordPolicy::allows(str_repeat('é', 11)));
        self::assertTrue(PasswordPolicy::allows(str_repeat('é', 12)));
        self::assertSame(12, PasswordPolicy::MIN_LENGTH);
    }

    public function testLegacyShortPasswordHashesStillVerify(): void
    {
        if(!defined('AuthToken')) {
            define('AuthToken', 'authentication-hardening-test-token');
        }

        $legacyPassword = 'old5!';
        $legacyHash = password_hash($legacyPassword.AuthToken, PASSWORD_BCRYPT, ['cost' => 4]);

        self::assertTrue(Helper::validatePass($legacyPassword, $legacyHash));
        self::assertFalse(Helper::validatePass('wrong-password', $legacyHash));
    }

    private function throttle(
        int $maxAttempts = 10,
        ?object $cache = null,
        ?bool $cacheEnabled = null,
    ): AuthThrottle {
        $this->requireHelper('AuthThrottle');

        return new AuthThrottle(
            maxAttempts: $maxAttempts,
            windowSeconds: 3600,
            clock: static fn (): int => 1_700_000_000,
            fallbackDirectory: $this->fallbackDirectory,
            cache: $cache,
            cacheEnabled: $cacheEnabled,
        );
    }

    private function requireHelper(string $name): void
    {
        $path = $this->root.'/app/helpers/'.$name.'.php';

        self::assertFileExists($path, $name.' must be implemented as an authentication helper.');
        require_once $path;
    }

    private function read(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertNotFalse($source, $relativePath);

        return $source;
    }

    private function methodSource(string $source, string $method): string
    {
        $start = strpos($source, 'public function '.$method.'(');
        self::assertNotFalse($start, $method);
        $brace = strpos($source, '{', $start);
        self::assertNotFalse($brace, $method);
        $depth = 0;
        $length = strlen($source);

        for($offset = $brace; $offset < $length; $offset++) {
            if($source[$offset] === '{') {
                $depth++;
            } elseif($source[$offset] === '}') {
                $depth--;

                if($depth === 0) {
                    return substr($source, $start, $offset - $start + 1);
                }
            }
        }

        self::fail('Could not isolate '.$method.'.');
    }

    private function removeDirectory(string $directory): void
    {
        if(!is_dir($directory)) {
            return;
        }

        foreach(glob($directory.'/*') ?: [] as $path) {
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}

final class AuthThrottleCachePool
{
    /** @var array<string, AuthThrottleCacheItem> */
    public array $items = [];

    public function getItem(string $key): AuthThrottleCacheItem
    {
        return $this->items[$key] ??= new AuthThrottleCacheItem();
    }

    public function save(AuthThrottleCacheItem $item): bool
    {
        return true;
    }

    public function deleteItem(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }
}

final class AuthThrottleCacheItem
{
    public bool $hit = false;
    public int $value = 0;
    public int $incrementCalls = 0;
    public ?\DateTimeImmutable $expiresAt = null;

    public function isHit(): bool
    {
        return $this->hit;
    }

    public function get(): int
    {
        return $this->value;
    }

    public function set(int $value): self
    {
        $this->value = $value;
        $this->hit = true;

        return $this;
    }

    public function increment(int $step): self
    {
        $this->value += $step;
        $this->incrementCalls++;

        return $this;
    }

    public function expiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }
}
