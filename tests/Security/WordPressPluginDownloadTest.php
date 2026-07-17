<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use User\Integrations;
use ZipArchive;

require_once dirname(__DIR__, 2).'/app/controllers/user/IntegrationsController.php';

final class WordPressPluginDownloadTest extends TestCase
{
    public function testArchiveUsesPrivateUniqueTemporaryFilesAndIsRemovedAfterStreaming(): void
    {
        $this->requireHardenedDownloadBoundary();
        $download = new InspectableWordPressPluginDownload();

        $download->deliver('<?php // api-key-one');
        $download->deliver('<?php // api-key-two');

        self::assertCount(2, $download->paths);
        self::assertNotSame($download->paths[0], $download->paths[1]);
        self::assertSame(['<?php // api-key-one', '<?php // api-key-two'], $download->pluginContents);

        foreach ($download->paths as $index => $path) {
            self::assertSame(realpath(sys_get_temp_dir()), realpath(dirname($path)));
            self::assertSame(0600, $download->permissions[$index]);
            self::assertFileDoesNotExist($path);
        }
    }

    public function testArchiveIsRemovedWhenStreamingThrows(): void
    {
        $this->requireHardenedDownloadBoundary();
        $download = new InspectableWordPressPluginDownload();
        $download->throwWhileStreaming = true;

        try {
            $download->deliver('<?php // sensitive-api-key');
            self::fail('The simulated streaming failure was not raised.');
        } catch (RuntimeException $exception) {
            self::assertSame('simulated stream failure', $exception->getMessage());
        }

        self::assertCount(1, $download->paths);
        self::assertFileDoesNotExist($download->paths[0]);
    }

    public function testDownloadSendsPrivateNoStoreHeadersAndStreamsTheArchive(): void
    {
        $this->requireHardenedDownloadBoundary();
        $download = new CapturingWordPressPluginDownload();

        $payload = $download->deliverAndCapture('<?php // embedded-api-key');
        $headers = $download->headersFor(strlen($payload));

        self::assertContains('Content-Disposition: attachment; filename="linkshortenershortcode.zip"', $headers);
        self::assertContains('Content-Type: application/zip', $headers);
        self::assertContains('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', $headers);
        self::assertContains('Pragma: no-cache', $headers);
        self::assertContains('Expires: 0', $headers);
        self::assertContains('X-Content-Type-Options: nosniff', $headers);
        self::assertContains('Content-Length: '.strlen($payload), $headers);
        self::assertStringStartsWith("PK", $payload);
        self::assertNotSame('', $payload);
        self::assertNotNull($download->path);
        self::assertFileDoesNotExist($download->path);
    }

    private function requireHardenedDownloadBoundary(): void
    {
        self::assertTrue(
            method_exists(Integrations::class, 'deliverPluginArchive'),
            'The controller must provide a protected archive delivery boundary.'
        );
    }
}

class InspectableWordPressPluginDownload extends Integrations
{
    public array $paths = [];
    public array $pluginContents = [];
    public array $permissions = [];
    public bool $throwWhileStreaming = false;

    public function deliver(string $plugin): void
    {
        $this->deliverPluginArchive($plugin);
    }

    protected function streamPluginArchive(string $path): void
    {
        $this->paths[] = $path;
        $this->permissions[] = fileperms($path) & 0777;

        $zip = new ZipArchive();
        TestCase::assertTrue($zip->open($path));
        $contents = $zip->getFromName('plugin.php');
        $zip->close();
        TestCase::assertIsString($contents);
        $this->pluginContents[] = $contents;

        if ($this->throwWhileStreaming) {
            throw new RuntimeException('simulated stream failure');
        }
    }
}

final class CapturingWordPressPluginDownload extends Integrations
{
    public ?string $path = null;

    public function deliverAndCapture(string $plugin): string
    {
        ob_start();

        try {
            $this->deliverPluginArchive($plugin);

            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }

    public function headersFor(int $size): array
    {
        return $this->pluginDownloadHeaders($size);
    }

    protected function createPluginArchive(string $plugin): string
    {
        $path = parent::createPluginArchive($plugin);
        $this->path = $path;

        return $path;
    }
}
