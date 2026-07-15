<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Core\Request;
use Helpers\LinkTargeting;
use PHPUnit\Framework\TestCase;

final class LinkTargetingTest extends TestCase
{
    private array $server;
    private array $request;
    private array $files;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->request = $_REQUEST;
        $this->files = $_FILES;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_REQUEST = $this->request;
        $_FILES = $this->files;
    }

    public function testMissingAcceptLanguageProducesEmptyLanguage(): void
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        self::assertSame('', LinkTargeting::browserLanguage(new Request()));
    }

    public function testAcceptLanguageProducesLowercaseTwoCharacterLanguage(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ID-id,id;q=0.9';

        self::assertSame('id', LinkTargeting::browserLanguage(new Request()));
    }

    public function testNullUrlTypeDoesNotProduceOverlayId(): void
    {
        self::assertNull(LinkTargeting::overlayId(null));
    }

    public function testOverlayUrlTypeProducesOverlayId(): void
    {
        self::assertSame('42', LinkTargeting::overlayId('overlay-42'));
    }
}
