<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Core\Http;
use ErrorException;
use PHPUnit\Framework\TestCase;

final class CurlLifecycleTest extends TestCase
{
    public function testHttpRequestDoesNotExplicitlyCloseCurlHandle(): void
    {
        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        }, E_DEPRECATED);

        try {
            $response = Http::url('file://'.__FILE__)->get();
        } finally {
            restore_error_handler();
        }

        self::assertStringContainsString('CurlLifecycleTest', $response->getBody());
    }
}
