<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    private array $server;
    private array $request;
    private array $files;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->request = $_REQUEST;
        $this->files = $_FILES;

        $_REQUEST = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_REQUEST = $this->request;
        $_FILES = $this->files;
    }

    public function testConstructorDefaultsToGetWhenRequestMethodIsMissing(): void
    {
        unset($_SERVER['REQUEST_METHOD']);

        $request = new Request();

        self::assertSame('get', $request->typeof());
    }

    public function testServerStringReturnsDefaultForMissingHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $request = new Request();

        self::assertSame('', $request->serverString('HTTP_ACCEPT_LANGUAGE'));
        self::assertSame('en', $request->serverString('HTTP_ACCEPT_LANGUAGE', 'en'));
    }

    public function testServerStringReturnsCleanScalarHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'id-ID,id;q=0.9';

        $request = new Request();

        self::assertSame('id-ID,id;q=0.9', $request->serverString('http_accept_language'));
    }
}
