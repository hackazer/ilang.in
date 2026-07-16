<?php

declare(strict_types=1);

namespace Tests\Security;

use Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestUploadSecurityTest extends TestCase
{
    private array $server;
    private array $request;
    private array $files;
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->request = $_REQUEST;
        $this->files = $_FILES;

        $_SERVER = ['REQUEST_METHOD' => 'POST'];
        $_REQUEST = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_REQUEST = $this->request;
        $_FILES = $this->files;

        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testMimeTypeComesFromUploadedBytesInsteadOfClientHeader(): void
    {
        $path = $this->temporaryFile(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        ));
        $_FILES['file'] = $this->upload($path, 'pixel.png', 'text/plain', 1);

        $file = (new Request())->file('file');

        self::assertSame('image/png', $file->type);
        self::assertTrue($file->mimematch);
        self::assertTrue($file->isvalid);
        self::assertSame(filesize($path), $file->size);
    }

    public function testSpoofedImageMimeIsRejectedFromPhpBytes(): void
    {
        $path = $this->temporaryFile('<?php echo "unsafe";');
        $_FILES['file'] = $this->upload($path, 'avatar.jpg', 'image/jpeg', filesize($path));

        $file = (new Request())->file('file');

        self::assertNotSame('image/jpeg', $file->type);
        self::assertFalse($file->mimematch);
        self::assertFalse($file->isvalid);
    }

    public function testActualFileSizeEnforcesSharedUploadCeiling(): void
    {
        $path = $this->temporaryFile('x');
        $handle = fopen($path, 'r+b');
        self::assertIsResource($handle);
        self::assertTrue(ftruncate($handle, Request::MAX_UPLOAD_BYTES + 1));
        fclose($handle);

        $_FILES['file'] = $this->upload($path, 'oversized.txt', 'text/plain', 1);

        $file = (new Request())->file('file');

        self::assertSame(Request::MAX_UPLOAD_BYTES + 1, $file->size);
        self::assertFalse($file->isvalid);
    }

    private function upload(string $path, string $name, string $type, int $reportedSize): array
    {
        return [
            'name' => $name,
            'type' => $type,
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
            'size' => $reportedSize,
        ];
    }

    private function temporaryFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'request-upload-');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
