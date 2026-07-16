<?php

declare(strict_types=1);

namespace Helpers;

use InvalidArgumentException;
use RuntimeException;

final class LinkPassword
{
    public const MAX_LENGTH = 64;

    public static function hash(?string $password): ?string
    {
        if ($password === null || $password === '') {
            return null;
        }

        if (strlen($password) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Link passwords cannot exceed '.self::MAX_LENGTH.' bytes.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($hash === false) {
            throw new RuntimeException('The link password could not be secured.');
        }

        return $hash;
    }

    public static function verifyAndUpgrade(string $password, object $link): bool
    {
        if ($password === '' || strlen($password) > self::MAX_LENGTH) {
            return false;
        }

        $stored = (string) ($link->pass ?? '');

        if ($stored === '') {
            return false;
        }

        $info = password_get_info($stored);

        if (($info['algoName'] ?? 'unknown') !== 'unknown') {
            return password_verify($password, $stored);
        }

        $valid = hash_equals($stored, $password);

        if (preg_match('/\A[a-f0-9]{32}\z/i', $stored) === 1) {
            $valid = hash_equals(strtolower($stored), md5($password)) || $valid;
        }

        if (!$valid) {
            return false;
        }

        $link->pass = self::hash($password);
        $link->save();

        return true;
    }
}
