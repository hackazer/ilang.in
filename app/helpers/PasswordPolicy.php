<?php

declare(strict_types=1);

namespace Helpers;

final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    public static function allows($password): bool
    {
        if(!is_string($password)) {
            return false;
        }

        return self::length($password) >= self::MIN_LENGTH;
    }

    public static function message(): string
    {
        return 'Password must be at least '.self::MIN_LENGTH.' characters.';
    }

    private static function length(string $password): int
    {
        if(preg_match('//u', $password) === 1) {
            $length = preg_match_all('/./us', $password);

            if($length !== false) {
                return $length;
            }
        }

        return strlen($password);
    }
}
