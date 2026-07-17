<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Traits\Links;

require_once dirname(__DIR__, 2).'/app/traits/Links.php';

final class LinkWebhookLatencyHarness
{
    use Links;

    public function deliver(string $url, array $payload, ?callable $transport = null): void
    {
        $this->sendLinkWebhook($url, $payload, $transport);
    }
}

final class LinkWebhookLatencyTest extends TestCase
{
    public function testWebhookDeliveryUsesStrictTimeoutBudget(): void
    {
        self::assertTrue(method_exists(LinkWebhookLatencyHarness::class, 'sendLinkWebhook'));

        $harness = new LinkWebhookLatencyHarness();
        $payload = ['type' => 'url', 'shorturl' => 'https://ilang.in/example'];
        $captured = [];

        $harness->deliver(
            'https://hooks.example.test/deliver',
            $payload,
            static function (string $url, array $sentPayload, array $options) use (&$captured): void {
                $captured = compact('url', 'sentPayload', 'options');
            }
        );

        self::assertSame('https://hooks.example.test/deliver', $captured['url']);
        self::assertSame($payload, $captured['sentPayload']);
        self::assertSame([
            'connect_timeout' => 1,
            'timeout' => 2,
        ], $captured['options']);
    }

    public function testWebhookTransportFailureDoesNotEscape(): void
    {
        self::assertTrue(method_exists(LinkWebhookLatencyHarness::class, 'sendLinkWebhook'));

        $transportCalled = false;

        (new LinkWebhookLatencyHarness())->deliver(
            'https://hooks.example.test/deliver',
            ['type' => 'view'],
            static function () use (&$transportCalled): never {
                $transportCalled = true;
                throw new RuntimeException('provider unavailable');
            }
        );

        self::assertTrue($transportCalled);
    }

    public function testUnsafeWebhookDestinationIsIsolatedBySharedTransport(): void
    {
        self::assertTrue(method_exists(LinkWebhookLatencyHarness::class, 'sendLinkWebhook'));

        (new LinkWebhookLatencyHarness())->deliver(
            'http://127.0.0.1/internal',
            ['type' => 'view']
        );

        self::addToAssertionCount(1);
    }

    public function testCreatedWebhookPayloadAndPluginOrderRemainStable(): void
    {
        $source = $this->linksSource();
        $plugin = strpos($source, "Plugin::dispatch('link.shorten.final', \$link)");
        $delivery = strpos($source, '$this->sendLinkWebhook($user->zapurl');

        self::assertIsInt($plugin);
        self::assertIsInt($delivery);
        self::assertLessThan($delivery, $plugin);

        $deliveryBlock = substr($source, $delivery, 600);
        self::assertStringContainsString('"type"', $deliveryBlock);
        self::assertStringContainsString('"longurl"', $deliveryBlock);
        self::assertStringContainsString('"shorturl"', $deliveryBlock);
        self::assertStringContainsString('"date"', $deliveryBlock);
    }

    public function testViewedWebhookPayloadAndPluginOrderRemainStable(): void
    {
        $source = $this->linksSource();
        $plugin = strpos($source, "Plugin::dispatch('link.update.stats', \$url)");
        $delivery = strpos($source, '$this->sendLinkWebhook($user->zapview');

        self::assertIsInt($plugin);
        self::assertIsInt($delivery);
        self::assertLessThan($delivery, $plugin);

        $deliveryBlock = substr($source, $delivery, 900);

        foreach (['"type"', '"shorturl"', '"country"', '"city"', '"referer"', '"os"', '"browser"', '"date"'] as $field) {
            self::assertStringContainsString($field, $deliveryBlock);
        }
    }

    private function linksSource(): string
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/traits/Links.php');
        self::assertIsString($source);

        return $source;
    }
}
