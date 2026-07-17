<?php
/**
 * =======================================================================================
 *                           GemFramework (c) GemPixel                                     
 * ---------------------------------------------------------------------------------------
 *  This software is packaged with an exclusive framework as such distribution
 *  or modification of this framework is not allowed before prior consent from
 *  GemPixel. If you find that this framework is packaged in a software not distributed 
 *  by GemPixel or authorized parties, you must not use this software and contact GemPixel
 *  at https://gempixel.com/contact to inform them of this misuse.
 * =======================================================================================
 *
 * @package GemPixel\Premium-URL-Shortener
 * @author GemPixel (https://gempixel.com) 
 * @license https://gempixel.com/licenses
 * @link https://gempixel.com  
 */
namespace Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Helper;

final class ShortenThrottle extends Middleware {

    /**
     * Rate limiter
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     */
    private static $ratelimiter = [5, 1];

    /** @var callable|null */
    private $identityResolver;

    /** @var callable|null */
    private $counter;

    /** @var callable */
    private $clock;

    private string $fallbackDirectory;

    public function __construct(
        ?callable $identityResolver = null,
        ?callable $counter = null,
        ?callable $clock = null,
        ?string $fallbackDirectory = null,
    ) {
        $this->identityResolver = $identityResolver;
        $this->counter = $counter;
        $this->clock = $clock ?? static fn (): int => time();
        $this->fallbackDirectory = rtrim(
            $fallbackDirectory ?? sys_get_temp_dir().'/ilang-shorten-throttle',
            DIRECTORY_SEPARATOR,
        );
    }

    /**
     * Throttle API
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function handle(Request $request){
        $decision = $this->attempt($request);
        $response = new Response();
        $response->setHeader(['X-RateLimit-Limit', self::$ratelimiter[0]]);
        $response->setHeader(['X-RateLimit-Remaining', $decision['remaining']]);
        $response->setHeader(['X-RateLimit-Reset', $decision['reset_at']]);

        if(!$decision['allowed']) {
            $retryAfter = max(1, $decision['reset_at'] - $decision['now']);
            $response->setStatusCode(429);
            $response->setHeader(['Retry-After', $retryAfter]);
            $response->setBody([
                'error' => 429,
                'message' => 'Too Many Requests. Please retry later.',
                'Retry-After' => $retryAfter,
            ])->json();
            exit;
        }

        return true;
    }

    /**
     * Evaluate one shortening attempt without emitting a response.
     */
    public function attempt(Request $request): array {
        $now = max(0, (int) call_user_func($this->clock));
        $windowSeconds = max(1, 60 * (int) self::$ratelimiter[1]);
        $window = intdiv($now, $windowSeconds);
        $resetAt = ($window + 1) * $windowSeconds;
        [$identityType, $identityValue] = $this->identity($request);
        $key = hash('sha256', implode('|', [
            'shorten-v2',
            $identityType,
            $identityValue,
            (string) $window,
        ]));

        try {
            $observedCount = $this->consume($key, self::$ratelimiter[0], $resetAt);
        } catch (\Throwable $cacheFailure) {
            try {
                $observedCount = $this->consumeFallback($key, self::$ratelimiter[0], $resetAt);
            } catch (\Throwable $fallbackFailure) {
                $observedCount = self::$ratelimiter[0] + 1;
            }
        }

        $allowed = $observedCount <= self::$ratelimiter[0];
        $count = min(max(0, $observedCount), self::$ratelimiter[0]);

        return [
            'allowed' => $allowed,
            'count' => $count,
            'remaining' => max(0, self::$ratelimiter[0] - $count),
            'reset_at' => $resetAt,
            'now' => $now,
            'key' => $key,
        ];
    }

    private function identity(Request $request): array {
        $identity = $this->identityResolver
            ? call_user_func($this->identityResolver, $request)
            : $this->resolveIdentity($request);

        if(!is_array($identity) || count($identity) < 2) {
            $identity = ['anonymous', $request->ip()];
        }

        $type = in_array($identity[0], ['user', 'api', 'anonymous'], true)
            ? $identity[0]
            : 'anonymous';
        $value = is_scalar($identity[1]) ? trim((string) $identity[1]) : '';

        return [$type, $value !== '' ? $value : 'unknown'];
    }

    private function resolveIdentity(Request $request): array {
        try {
            if(\Core\Auth::check() && $user = \Core\Auth::user()) {
                return ['user', $this->userIdentity($user)];
            }
        } catch (\Throwable $error) {
        }

        $authorization = trim($request->serverString('HTTP_AUTHORIZATION'));

        if($authorization !== '' && preg_match('/^(?:Bearer|Token)\s+(.+)$/i', $authorization, $matches)) {
            try {
                if($apiUser = \Core\Auth::ApiUser(trim($matches[1]))) {
                    return ['api', $this->userIdentity($apiUser)];
                }
            } catch (\Throwable $error) {
            }
        }

        return ['anonymous', $request->ip()];
    }

    private function userIdentity(object $user): string {
        if(method_exists($user, 'rID')) {
            return (string) $user->rID();
        }

        return isset($user->id) ? (string) $user->id : 'unknown';
    }

    private function consume(string $key, int $limit, int $resetAt): int {
        if($this->counter) {
            return (int) call_user_func($this->counter, $key, $limit, $resetAt);
        }

        if(defined('CACHE') && CACHE === true) {
            return $this->consumeCache($key, $limit, $resetAt);
        }

        return $this->consumeFallback($key, $limit, $resetAt);
    }

    private function consumeCache(string $key, int $limit, int $resetAt): int {
        return $this->withLock($key.'.cache.lock', function () use ($key, $limit, $resetAt): int {
            $cache = Helper::cacheInstance();

            if(!is_object($cache) || !method_exists($cache, 'getItem') || !method_exists($cache, 'save')) {
                throw new \RuntimeException('The configured cache cannot store rate-limit counters.');
            }

            $item = $cache->getItem('shorten_'.$key);
            $wasHit = $item->isHit();
            $count = $wasHit && is_numeric($item->get()) ? max(0, (int) $item->get()) : 0;

            if($count >= $limit) {
                return $limit + 1;
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
                throw new \RuntimeException('The configured cache rejected the rate-limit update.');
            }

            return $count + 1;
        });
    }

    private function consumeFallback(string $key, int $limit, int $resetAt): int {
        $this->ensureDirectory($this->fallbackDirectory);
        $path = $this->fallbackDirectory.'/'.$key.'.json';
        $handle = fopen($path, 'c+b');

        if(!is_resource($handle)) {
            throw new \RuntimeException('Unable to open the fallback rate-limit counter.');
        }

        @chmod($path, 0600);

        try {
            if(!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock the fallback rate-limit counter.');
            }

            rewind($handle);
            $stored = json_decode((string) stream_get_contents($handle), true);
            $count = is_array($stored)
                && isset($stored['reset_at'], $stored['count'])
                && (int) $stored['reset_at'] === $resetAt
                ? max(0, (int) $stored['count'])
                : 0;

            if($count >= $limit) {
                return $limit + 1;
            }

            $count++;
            $payload = json_encode(['count' => $count, 'reset_at' => $resetAt], JSON_THROW_ON_ERROR);
            rewind($handle);

            if(!ftruncate($handle, 0) || fwrite($handle, $payload) === false || !fflush($handle)) {
                throw new \RuntimeException('Unable to persist the fallback rate-limit counter.');
            }

            return $count;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function withLock(string $name, callable $operation): int {
        $directory = $this->fallbackDirectory.'/locks';
        $this->ensureDirectory($directory);
        $path = $directory.'/'.$name;
        $handle = fopen($path, 'c+b');

        if(!is_resource($handle)) {
            throw new \RuntimeException('Unable to open the rate-limit lock.');
        }

        @chmod($path, 0600);

        try {
            if(!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock the rate-limit counter.');
            }

            return (int) call_user_func($operation);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureDirectory(string $directory): void {
        if(is_dir($directory)) {
            return;
        }

        if(!mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the rate-limit storage directory.');
        }
    }
}
