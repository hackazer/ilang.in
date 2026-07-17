<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use JsonException;

final class Signature
{
    public static function canonicalJson(array $payload): string
    {
        try {
            return json_encode(
                self::sortRecursively($payload),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('NOWPayments payload cannot be canonicalized.', 0, $exception);
        }
    }

    public static function verify(array $payload, string $provided, string $secret): bool
    {
        if ($provided === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha512', self::canonicalJson($payload), trim($secret));

        return hash_equals($expected, strtolower(trim($provided)));
    }

    public static function payloadHash(array $payload): string
    {
        return hash('sha256', self::canonicalJson($payload));
    }

    private static function sortRecursively(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(
                static fn (mixed $item): mixed => is_array($item) ? self::sortRecursively($item) : $item,
                $value
            );
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sortRecursively($item);
            }
        }

        return $value;
    }
}
