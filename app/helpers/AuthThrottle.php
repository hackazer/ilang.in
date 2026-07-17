<?php

declare(strict_types=1);

namespace Helpers;

use Core\Helper;

final class AuthThrottle
{
    public const LOGIN_SCOPE = 'login';
    public const TWO_FACTOR_SCOPE = '2fa';

    /** @var callable */
    private $clock;

    private int $maxAttempts;
    private int $windowSeconds;
    private string $fallbackDirectory;
    private ?object $cache;
    private bool $cacheEnabled;

    public function __construct(
        int $maxAttempts = 10,
        int $windowSeconds = 3600,
        ?callable $clock = null,
        ?string $fallbackDirectory = null,
        ?object $cache = null,
        ?bool $cacheEnabled = null,
    ) {
        $this->maxAttempts = max(1, $maxAttempts);
        $this->windowSeconds = max(1, $windowSeconds);
        $this->clock = $clock ?? static fn (): int => time();
        $this->fallbackDirectory = rtrim(
            $fallbackDirectory ?? sys_get_temp_dir().'/ilang-auth-throttle',
            DIRECTORY_SEPARATOR,
        );
        $this->cache = $cache;
        $this->cacheEnabled = $cacheEnabled ?? (defined('CACHE') && CACHE === true);
    }

    public function key(string $scope, string $identity, string $ip): string
    {
        return hash('sha256', implode('|', [
            'auth-v1',
            $this->normalizeScope($scope),
            $this->normalizeIdentity($identity),
            $this->normalizeIp($ip),
        ]));
    }

    public function isBlocked(string $scope, string $identity, string $ip): bool
    {
        return $this->count($this->key($scope, $identity, $ip)) >= $this->maxAttempts;
    }

    public function recordFailure(string $scope, string $identity, string $ip): int
    {
        $key = $this->key($scope, $identity, $ip);
        $resetAt = $this->now() + $this->windowSeconds;
        $counts = [];

        if($this->cacheEnabled) {
            try {
                $counts[] = $this->recordCache($key, $resetAt);
            } catch(\Throwable $error) {
            }
        }

        try {
            $counts[] = $this->recordFallback($key, $resetAt);
        } catch(\Throwable $error) {
        }

        return $counts ? max($counts) : $this->maxAttempts;
    }

    public function clear(string $scope, string $identity, string $ip): void
    {
        $key = $this->key($scope, $identity, $ip);

        if($this->cacheEnabled) {
            try {
                $cache = $this->cache();

                if(method_exists($cache, 'deleteItem')) {
                    $cache->deleteItem('auth_'.$key);
                }
            } catch(\Throwable $error) {
            }
        }

        $path = $this->fallbackPath($key);

        if(is_file($path)) {
            @unlink($path);
        }
    }

    private function count(string $key): int
    {
        $counts = [];

        if($this->cacheEnabled) {
            try {
                $counts[] = $this->readCache($key);
            } catch(\Throwable $error) {
            }
        }

        try {
            $counts[] = $this->readFallback($key);
        } catch(\Throwable $error) {
        }

        return $counts ? max($counts) : $this->maxAttempts;
    }

    private function readCache(string $key): int
    {
        $item = $this->cache()->getItem('auth_'.$key);

        return $item->isHit() && is_numeric($item->get())
            ? min($this->maxAttempts, max(0, (int) $item->get()))
            : 0;
    }

    private function recordCache(string $key, int $resetAt): int
    {
        return $this->withLock($key.'.cache.lock', function () use ($key, $resetAt): int {
            $cache = $this->cache();
            $item = $cache->getItem('auth_'.$key);
            $wasHit = $item->isHit();
            $count = $wasHit && is_numeric($item->get()) ? max(0, (int) $item->get()) : 0;

            if($count >= $this->maxAttempts) {
                return $this->maxAttempts;
            }

            if($wasHit && method_exists($item, 'increment')) {
                $item->increment(1);
            } else {
                $item->set($count + 1);
            }

            if(!$wasHit) {
                $item->expiresAt((new \DateTimeImmutable())->setTimestamp($resetAt));
            }

            if($cache->save($item) !== true) {
                throw new \RuntimeException('The configured cache rejected the authentication counter update.');
            }

            return $count + 1;
        });
    }

    private function readFallback(string $key): int
    {
        $path = $this->fallbackPath($key);

        if(!is_file($path)) {
            return 0;
        }

        return $this->withFile($path, function ($handle): int {
            rewind($handle);
            $stored = json_decode((string) stream_get_contents($handle), true);

            if(!is_array($stored) || !isset($stored['count'], $stored['reset_at'])) {
                return 0;
            }

            if((int) $stored['reset_at'] <= $this->now()) {
                return 0;
            }

            return min($this->maxAttempts, max(0, (int) $stored['count']));
        });
    }

    private function recordFallback(string $key, int $resetAt): int
    {
        $this->ensureDirectory($this->fallbackDirectory);

        return $this->withFile($this->fallbackPath($key), function ($handle) use ($resetAt): int {
            rewind($handle);
            $stored = json_decode((string) stream_get_contents($handle), true);
            $count = is_array($stored)
                && isset($stored['count'], $stored['reset_at'])
                && (int) $stored['reset_at'] > $this->now()
                ? max(0, (int) $stored['count'])
                : 0;

            if($count >= $this->maxAttempts) {
                return $this->maxAttempts;
            }

            $count++;
            $payload = json_encode(
                ['count' => $count, 'reset_at' => $count === 1 ? $resetAt : (int) $stored['reset_at']],
                JSON_THROW_ON_ERROR,
            );
            rewind($handle);

            if(!ftruncate($handle, 0) || fwrite($handle, $payload) === false || !fflush($handle)) {
                throw new \RuntimeException('Unable to persist the authentication rate-limit counter.');
            }

            return $count;
        });
    }

    private function cache(): object
    {
        $cache = $this->cache ?? Helper::cacheInstance();

        if(!is_object($cache)
            || !method_exists($cache, 'getItem')
            || !method_exists($cache, 'save')) {
            throw new \RuntimeException('The configured cache cannot store authentication counters.');
        }

        return $cache;
    }

    private function withLock(string $name, callable $operation): int
    {
        $directory = $this->fallbackDirectory.'/locks';
        $this->ensureDirectory($directory);

        return $this->withFile($directory.'/'.$name, static fn ($handle): int => (int) $operation());
    }

    private function withFile(string $path, callable $operation): int
    {
        $handle = fopen($path, 'c+b');

        if(!is_resource($handle)) {
            throw new \RuntimeException('Unable to open the authentication rate-limit store.');
        }

        @chmod($path, 0600);

        try {
            if(!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock the authentication rate-limit store.');
            }

            return (int) $operation($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function fallbackPath(string $key): string
    {
        return $this->fallbackDirectory.'/'.$key.'.json';
    }

    private function ensureDirectory(string $directory): void
    {
        if(is_dir($directory)) {
            return;
        }

        if(!mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the authentication rate-limit directory.');
        }
    }

    private function now(): int
    {
        return max(0, (int) call_user_func($this->clock));
    }

    private function normalizeScope(string $scope): string
    {
        $scope = strtolower(trim($scope));
        $scope = preg_replace('/[^a-z0-9_-]+/', '', $scope) ?? '';

        return $scope !== '' ? $scope : 'authentication';
    }

    private function normalizeIdentity(string $identity): string
    {
        $identity = trim($identity);

        if($identity === '') {
            return 'unknown';
        }

        if(function_exists('mb_strtolower') && preg_match('//u', $identity) === 1) {
            return mb_strtolower($identity, 'UTF-8');
        }

        return strtolower($identity);
    }

    private function normalizeIp(string $ip): string
    {
        $ip = trim($ip);

        if(str_starts_with(strtolower($ip), '::ffff:')) {
            $mapped = substr($ip, 7);

            if(filter_var($mapped, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                $ip = $mapped;
            }
        }

        $packed = @inet_pton($ip);

        if($packed === false) {
            return 'unknown';
        }

        $normalized = inet_ntop($packed);

        return is_string($normalized) ? strtolower($normalized) : 'unknown';
    }
}
