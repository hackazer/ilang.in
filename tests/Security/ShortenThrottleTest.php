<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Request;
use Core\Helper;
use Middleware\ShortenThrottle;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/core/Middleware.class.php';
require_once dirname(__DIR__, 2).'/app/middleware/ShortenThrottle.php';

final class ShortenThrottleTest extends TestCase
{
    private array $server;
    private array $request;
    private array $files;
    private array $session;
    private string $fallbackDirectory;

    protected function setUp(): void
    {
        self::assertTrue(
            method_exists(ShortenThrottle::class, 'attempt'),
            'ShortenThrottle must expose a testable throttle decision before emitting a response.',
        );

        $this->server = $_SERVER;
        $this->request = $_REQUEST;
        $this->files = $_FILES;
        $this->session = $_SESSION ?? [];
        $this->fallbackDirectory = sys_get_temp_dir().'/ilang-shorten-throttle-test-'.bin2hex(random_bytes(8));

        $_REQUEST = [];
        $_FILES = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_REQUEST = $this->request;
        $_FILES = $this->files;
        $_SESSION = $this->session;

        $this->removeDirectory($this->fallbackDirectory);
    }

    public function testAnonymousClientsCannotResetTheLimitByRotatingSessions(): void
    {
        $now = 1_700_000_000;
        $counts = [];
        $seenKeys = [];
        $throttle = $this->throttle(
            identityResolver: static fn (Request $request): array => ['anonymous', $request->ip()],
            counter: static function (string $key, int $limit, int $resetAt) use (&$counts, &$seenKeys): int {
                $seenKeys[] = $key;
                $counts[$key] = ($counts[$key] ?? 0) + 1;

                return $counts[$key];
            },
            clock: static fn (): int => $now,
        );

        $request = $this->requestFrom('203.0.113.25');
        $decisions = [];

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $_SESSION = ['throttlekey' => 'disposable-'.$attempt];
            $decisions[] = $throttle->attempt($request);
        }

        self::assertTrue($decisions[4]['allowed']);
        self::assertFalse($decisions[5]['allowed']);
        self::assertCount(1, array_unique($seenKeys));
    }

    public function testAuthenticatedAndApiIdentitiesAreStableAcrossIpChanges(): void
    {
        $counts = [];
        $counter = static function (string $key, int $limit, int $resetAt) use (&$counts): int {
            return $counts[$key] = ($counts[$key] ?? 0) + 1;
        };

        $userThrottle = $this->throttle(
            identityResolver: static fn (Request $request): array => ['user', 42],
            counter: $counter,
        );
        $apiThrottle = $this->throttle(
            identityResolver: static fn (Request $request): array => ['api', 42],
            counter: $counter,
        );

        $userFirst = $userThrottle->attempt($this->requestFrom('203.0.113.10'));
        $userSecond = $userThrottle->attempt($this->requestFrom('198.51.100.10'));
        $apiFirst = $apiThrottle->attempt($this->requestFrom('192.0.2.10'));

        self::assertSame(1, $userFirst['count']);
        self::assertSame(2, $userSecond['count']);
        self::assertSame(1, $apiFirst['count']);
        self::assertNotSame($userFirst['key'], $apiFirst['key']);
    }

    public function testAnonymousAddressesHaveIndependentLimits(): void
    {
        $counts = [];
        $throttle = $this->throttle(
            identityResolver: static fn (Request $request): array => ['anonymous', $request->ip()],
            counter: static function (string $key, int $limit, int $resetAt) use (&$counts): int {
                return $counts[$key] = ($counts[$key] ?? 0) + 1;
            },
        );

        $first = $throttle->attempt($this->requestFrom('203.0.113.1'));
        $second = $throttle->attempt($this->requestFrom('203.0.113.2'));

        self::assertSame(1, $first['count']);
        self::assertSame(1, $second['count']);
        self::assertNotSame($first['key'], $second['key']);
    }

    public function testStableWindowResetsAtTheNextBoundary(): void
    {
        $now = 1_700_000_000;
        $counts = [];
        $throttle = $this->throttle(
            identityResolver: static fn (Request $request): array => ['anonymous', $request->ip()],
            counter: static function (string $key, int $limit, int $resetAt) use (&$counts): int {
                return $counts[$key] = ($counts[$key] ?? 0) + 1;
            },
            clock: static function () use (&$now): int {
                return $now;
            },
        );
        $request = $this->requestFrom('203.0.113.50');

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $decision = $throttle->attempt($request);
        }

        self::assertFalse($decision['allowed']);

        $now = $decision['reset_at'];
        $resetDecision = $throttle->attempt($request);

        self::assertTrue($resetDecision['allowed']);
        self::assertSame(1, $resetDecision['count']);
        self::assertNotSame($decision['key'], $resetDecision['key']);
    }

    public function testCacheFailureFallsBackToAnEnforcedCounter(): void
    {
        $throttle = $this->throttle(
            identityResolver: static fn (Request $request): array => ['anonymous', $request->ip()],
            counter: static function (string $key, int $limit, int $resetAt): int {
                throw new \RuntimeException('cache unavailable');
            },
        );
        $request = $this->requestFrom('203.0.113.75');

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $decision = $throttle->attempt($request);
        }

        self::assertFalse($decision['allowed']);
        self::assertSame(5, $decision['count']);
        self::assertFileExists($this->fallbackDirectory.'/'.$decision['key'].'.json');
    }

    public function testCacheCounterUsesIncrementAndKeepsTheWindowExpiry(): void
    {
        $previousCache = Helper::cacheInstance();
        $cache = new ShortenThrottleCachePool();
        Helper::set('cacheInstance', $cache);
        $throttle = $this->throttle();
        $consumeCache = new \ReflectionMethod($throttle, 'consumeCache');

        try {
            self::assertSame(1, $consumeCache->invoke($throttle, 'cache-key', 5, 1_700_000_040));
            self::assertSame(1_700_000_040, $cache->item->expiresAt?->getTimestamp());

            self::assertSame(2, $consumeCache->invoke($throttle, 'cache-key', 5, 1_700_000_040));
            self::assertSame(1, $cache->item->incrementCalls);
            self::assertSame(2, $cache->item->value);
            self::assertSame(2, $cache->saveCalls);
        } finally {
            Helper::set('cacheInstance', $previousCache);
        }
    }

    private function throttle(
        ?callable $identityResolver = null,
        ?callable $counter = null,
        ?callable $clock = null,
    ): ShortenThrottle {
        return new ShortenThrottle(
            identityResolver: $identityResolver,
            counter: $counter,
            clock: $clock ?? static fn (): int => 1_700_000_000,
            fallbackDirectory: $this->fallbackDirectory,
        );
    }

    private function requestFrom(string $remoteAddress): Request
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => $remoteAddress,
        ];

        return new Request();
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory.'/*') ?: [] as $path) {
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}

final class ShortenThrottleCachePool
{
    public ShortenThrottleCacheItem $item;
    public int $saveCalls = 0;

    public function __construct()
    {
        $this->item = new ShortenThrottleCacheItem();
    }

    public function getItem(string $key): ShortenThrottleCacheItem
    {
        return $this->item;
    }

    public function save(ShortenThrottleCacheItem $item): bool
    {
        $this->saveCalls++;

        return true;
    }
}

final class ShortenThrottleCacheItem
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
