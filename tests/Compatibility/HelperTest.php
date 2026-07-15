<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Core\Helper;
use ErrorException;
use PHPUnit\Framework\TestCase;

final class HelperTest extends TestCase
{
    public function testRandPreservesPrefixLengthAndAllowedAlphabet(): void
    {
        $value = Helper::rand(24, 'prefix');

        self::assertSame(30, strlen($value));
        self::assertMatchesRegularExpression('/^prefix[a-zA-Z]{24}$/', $value);
    }

    public function testUsernameValidationDoesNotUseDeprecatedSanitization(): void
    {
        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        }, E_DEPRECATED);

        try {
            self::assertSame('valid_user', Helper::username('valid_user'));
        } finally {
            restore_error_handler();
        }
    }
}
