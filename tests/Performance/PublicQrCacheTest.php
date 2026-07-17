<?php

declare(strict_types=1);

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

require_once dirname(__DIR__, 2).'/app/controllers/QRController.php';

final class PublicQrCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $directory = sys_get_temp_dir().'/public-qr-cache-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory, 0700));
        $this->directory = $directory;
    }

    protected function tearDown(): void
    {
        foreach ($this->files() as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function testCachePublicationKeepsPartialOutputHiddenUntilAtomicRename(): void
    {
        $method = $this->publisher();
        $filename = $this->filename('public-alias');
        $finalPath = $this->directory.'/'.$filename;
        $persisted = false;

        $method->invoke(
            null,
            $this->directory,
            $filename,
            function (string $temporaryPath) use (&$persisted, $finalPath): void {
                self::assertTrue($persisted);
                self::assertSame(realpath($this->directory), realpath(dirname($temporaryPath)));
                self::assertNotSame($finalPath, $temporaryPath);

                file_put_contents($temporaryPath, 'partial');
                self::assertFileDoesNotExist($finalPath);
                file_put_contents($temporaryPath, 'complete-png');
            },
            function () use (&$persisted, $finalPath): void {
                self::assertFileDoesNotExist($finalPath);
                $persisted = true;
            }
        );

        self::assertTrue($persisted);
        self::assertSame('complete-png', file_get_contents($finalPath));
        self::assertSame([$finalPath], $this->files());
    }

    public function testExistingPublishedFileWinsWithoutCreatingAnotherCandidate(): void
    {
        $method = $this->publisher();
        $filename = $this->filename('shared-alias');
        $finalPath = $this->directory.'/'.$filename;
        file_put_contents($finalPath, 'winner-png');
        $persistCalls = 0;

        $method->invoke(
            null,
            $this->directory,
            $filename,
            static function (): void {
                self::fail('A concurrent winner must prevent duplicate rendering.');
            },
            static function () use (&$persistCalls): void {
                $persistCalls++;
            }
        );

        self::assertSame(1, $persistCalls);
        self::assertSame('winner-png', file_get_contents($finalPath));
        self::assertSame([$finalPath], $this->files());
    }

    public function testRenderFailureRemovesTemporaryOutput(): void
    {
        $method = $this->publisher();
        $filename = $this->filename('failed-alias');
        $persisted = false;

        try {
            $method->invoke(
                null,
                $this->directory,
                $filename,
                static function (string $temporaryPath) use (&$persisted): void {
                    self::assertTrue($persisted);
                    file_put_contents($temporaryPath, 'partial');
                    throw new RuntimeException('render failed');
                },
                static function () use (&$persisted): void {
                    $persisted = true;
                }
            );
            self::fail('The render exception must be propagated.');
        } catch (RuntimeException $exception) {
            self::assertSame('render failed', $exception->getMessage());
        }

        self::assertSame([], $this->files());
    }

    public function testPersistenceFailureCreatesNoCacheOutput(): void
    {
        $method = $this->publisher();
        $filename = $this->filename('retry-alias');

        try {
            $method->invoke(
                null,
                $this->directory,
                $filename,
                static function (): void {
                    self::fail('Rendering must not start before persistence succeeds.');
                },
                static function (): void {
                    throw new RuntimeException('database failed');
                }
            );
            self::fail('The persistence exception must be propagated.');
        } catch (RuntimeException $exception) {
            self::assertSame('database failed', $exception->getMessage());
        }

        self::assertSame([], $this->files());
    }

    private function publisher(): ReflectionMethod
    {
        self::assertTrue(
            method_exists(\QR::class, 'publishCacheFile'),
            'Public QR generation must publish cache files atomically.'
        );

        return new ReflectionMethod(\QR::class, 'publishCacheFile');
    }

    private function filename(string $alias): string
    {
        self::assertTrue(
            method_exists(\QR::class, 'cacheFilename'),
            'Concurrent requests must derive the same final cache filename.'
        );

        $method = new ReflectionMethod(\QR::class, 'cacheFilename');
        $first = $method->invoke(null, $alias);
        $second = $method->invoke(null, $alias);

        self::assertIsString($first);
        self::assertSame($first, $second);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+-[a-f0-9]{16}\.png$/', $first);

        return $first;
    }

    /** @return list<string> */
    private function files(): array
    {
        $entries = array_values(array_diff(scandir($this->directory) ?: [], ['.', '..']));
        sort($entries);

        return array_map(fn(string $entry): string => $this->directory.'/'.$entry, $entries);
    }
}
