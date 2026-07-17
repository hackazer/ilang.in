<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QrGdCompatibilityTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    protected function setUp(): void
    {
        require_once self::ROOT.'/app/helpers/QrGd.php';
    }

    public function testComposerUsesPhp83CompatibleQrDependenciesDirectly(): void
    {
        $composer = json_decode(
            (string) file_get_contents(self::ROOT.'/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('^6.0', $composer['require']['chillerlan/php-qrcode'] ?? null);
        self::assertSame('^3.1', $composer['require']['bacon/bacon-qr-code'] ?? null);
        self::assertArrayNotHasKey('endroid/qr-code', $composer['require']);

        $lock = json_decode(
            (string) file_get_contents(self::ROOT.'/composer.lock'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $versions = array_column($lock['packages'], 'version', 'name');

        self::assertSame('6.0.1', $versions['chillerlan/php-qrcode'] ?? null);
        self::assertSame('v3.1.1', $versions['bacon/bacon-qr-code'] ?? null);
        self::assertArrayNotHasKey('endroid/qr-code', $versions);
    }

    #[DataProvider('formatProvider')]
    public function testEveryFormatKeepsItsExtensionAndDataUriMimeType(string $format, string $prefix): void
    {
        $qr = (new \Helpers\QRGd('format-contract', 120, 5))->format($format);

        self::assertSame($format, $qr->extension());
        self::assertStringStartsWith($prefix, $qr->create('uri'));
    }

    public static function formatProvider(): iterable
    {
        yield 'PNG' => ['png', 'data:image/png;base64,'];
        yield 'SVG' => ['svg', 'data:image/svg+xml;base64,'];
        yield 'PDF' => ['pdf', 'data:application/pdf;base64,'];
    }

    public function testPngOutputPreservesRequestedSizePlusSymmetricMarginsAndCssRgbColors(): void
    {
        $uri = (new \Helpers\QRGd('color-contract', 120, 5))
            ->color('rgb(10, 20, 30)', 'rgb(240, 245, 250)')
            ->format('png')
            ->create('uri');

        $image = imagecreatefromstring($this->decodeDataUri($uri));

        self::assertInstanceOf(\GdImage::class, $image);
        self::assertSame(130, imagesx($image));
        self::assertSame(130, imagesy($image));

        $colors = [];

        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $rgb = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $colors[$rgb['red'].','.$rgb['green'].','.$rgb['blue']] = true;
            }
        }

        self::assertArrayHasKey('10,20,30', $colors);
        self::assertArrayHasKey('240,245,250', $colors);
    }

    public function testGeneratedPngDecodesBackToItsUtf8Payload(): void
    {
        $payload = 'https://ilang.in/compatibility/你好?source=php83';
        $uri = (new \Helpers\QRGd($payload, 240, 12))->format('png')->create('uri');

        $result = (new \chillerlan\QRCode\QRCode())->readFromBlob($this->decodeDataUri($uri));

        self::assertSame($payload, $result->data);
    }

    #[DataProvider('invalidColorProvider')]
    public function testInvalidOrNullColorsFallBackWithoutPhpWarnings(mixed $foreground, mixed $background): void
    {
        $errors = [];
        set_error_handler(static function (int $severity, string $message) use (&$errors): bool {
            $errors[] = [$severity, $message];

            return true;
        });

        try {
            $uri = (new \Helpers\QRGd('safe-color-contract', 120, 5))
                ->color($foreground, $background)
                ->format('svg')
                ->create('uri');
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $errors);
        self::assertStringStartsWith('data:image/svg+xml;base64,', $uri);
    }

    public static function invalidColorProvider(): iterable
    {
        yield 'null values' => [null, null];
        yield 'malformed values' => ['rgb(999, nope, -1)', 'transparent'];
        yield 'arrays' => [[], []];
    }

    public function testLogoIsCenteredInPngOutput(): void
    {
        $directory = $this->temporaryDirectory();
        $logoPath = $directory.'/logo.png';
        $logo = imagecreatetruecolor(20, 20);
        $red = imagecolorallocate($logo, 255, 0, 0);
        imagefill($logo, 0, 0, $red);
        imagepng($logo, $logoPath);

        $uri = (new \Helpers\QRGd('logo-contract', 160, 8))
            ->withLogo($logoPath, 40)
            ->format('png')
            ->create('uri');

        $image = imagecreatefromstring($this->decodeDataUri($uri));
        self::assertInstanceOf(\GdImage::class, $image);

        $center = imagecolorsforindex($image, imagecolorat($image, 88, 88));
        self::assertSame([255, 0, 0], [$center['red'], $center['green'], $center['blue']]);

        unlink($logoPath);
        rmdir($directory);
    }

    #[DataProvider('transparentLogoFormatProvider')]
    public function testTransparentLogoWorksInSvgAndPdfOutput(string $format, string $prefix): void
    {
        $directory = $this->temporaryDirectory();
        $logoPath = $directory.'/transparent-logo.png';
        $logo = imagecreatetruecolor(20, 20);
        imagealphablending($logo, false);
        imagesavealpha($logo, true);
        $transparent = imagecolorallocatealpha($logo, 255, 255, 255, 127);
        imagefill($logo, 0, 0, $transparent);
        $red = imagecolorallocatealpha($logo, 255, 0, 0, 0);
        imagefilledrectangle($logo, 5, 5, 14, 14, $red);
        imagepng($logo, $logoPath);

        $uri = (new \Helpers\QRGd('transparent-logo-contract', 160, 8))
            ->withLogo($logoPath, 40)
            ->format($format)
            ->create('uri');

        self::assertStringStartsWith($prefix, $uri);

        if($format === 'svg'){
            self::assertStringContainsString('<image', $this->decodeDataUri($uri));
        } else {
            self::assertStringStartsWith('%PDF-', $this->decodeDataUri($uri));
        }

        unlink($logoPath);
        rmdir($directory);
    }

    public static function transparentLogoFormatProvider(): iterable
    {
        yield 'SVG' => ['svg', 'data:image/svg+xml;base64,'];
        yield 'PDF' => ['pdf', 'data:application/pdf;base64,'];
    }

    public function testFileOutputUsesTheRequestedPathWithoutTemporaryArtifacts(): void
    {
        $directory = $this->temporaryDirectory();
        $target = $directory.'/code.svg';

        $result = (new \Helpers\QRGd('file-contract', 120, 5))
            ->format('svg')
            ->create('file', $target);

        self::assertTrue($result);
        self::assertFileExists($target);
        self::assertStringContainsString('<svg', (string) file_get_contents($target));
        self::assertSame(['code.svg'], array_values(array_diff(scandir($directory) ?: [], ['.', '..'])));

        unlink($target);
        rmdir($directory);
    }

    public function testFileOutputRejectsAnEmptyTarget(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new \Helpers\QRGd('invalid-file-contract'))->format('png')->create('file');
    }

    #[DataProvider('terminalOutputMethodProvider')]
    public function testTerminalOutputMethodsEmitRawContentAndExit(string $expression): void
    {
        $script = tempnam(sys_get_temp_dir(), 'ilang-qr-process-');
        self::assertIsString($script);

        $autoload = var_export(self::ROOT.'/vendor/autoload.php', true);
        $helper = var_export(self::ROOT.'/app/helpers/QrGd.php', true);
        $source = "<?php require {$autoload}; require {$helper}; {$expression}; echo 'UNREACHABLE';";
        file_put_contents($script, $source);

        $process = proc_open(
            [PHP_BINARY, $script],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        unlink($script);

        self::assertSame(0, $exitCode, (string) $stderr);
        self::assertStringContainsString('<svg', (string) $stdout);
        self::assertStringNotContainsString('UNREACHABLE', (string) $stdout);
    }

    public static function terminalOutputMethodProvider(): iterable
    {
        yield 'create raw' => ["(new \\Helpers\\QRGd('raw-exit'))->format('svg')->create()"];
        yield 'string' => ["(new \\Helpers\\QRGd('string-exit'))->format('svg')->string()"];
    }

    private function decodeDataUri(string $uri): string
    {
        $separator = strpos($uri, ',');
        self::assertNotFalse($separator);
        $decoded = base64_decode(substr($uri, $separator + 1), true);
        self::assertIsString($decoded);

        return $decoded;
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir().'/ilang-qr-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory, 0700));

        return $directory;
    }
}
