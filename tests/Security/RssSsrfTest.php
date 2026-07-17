<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\App;
use Helpers\OutboundUrl;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/app/helpers/App.php';

final class RssSsrfTest extends TestCase
{
    public function testRssRejectsPrivateDestinationsBeforeTransport(): void
    {
        $transportCalled = false;

        $result = App::rss(
            'http://feeds.example/private',
            static fn (string $host): array => ['127.0.0.1'],
            static function () use (&$transportCalled): bool {
                $transportCalled = true;
                return true;
            }
        );

        self::assertSame('Invalid RSS', $result);
        self::assertFalse($transportCalled);
    }

    public function testRssUsesPinnedBoundedTransportAndParsesValidFeed(): void
    {
        $xml = '<?xml version="1.0"?><rss version="2.0"><channel><item>'
            .'<title>Release</title><link>https://example.com/release</link>'
            .'<description>Safe update</description></item></channel></rss>';

        $items = App::rss(
            'https://feeds.example/latest.xml',
            static fn (string $host): array => ['93.184.216.34'],
            static function (string $url, array $options) use ($xml): bool {
                self::assertSame('https://feeds.example/latest.xml', $url);
                self::assertFalse($options[CURLOPT_FOLLOWLOCATION]);
                self::assertSame(0, $options[CURLOPT_MAXREDIRS]);
                self::assertSame(OutboundUrl::MAX_RESPONSE_BYTES, $options[CURLOPT_MAXFILESIZE]);
                self::assertSame(['feeds.example:443:93.184.216.34'], $options[CURLOPT_RESOLVE]);

                $writer = $options[CURLOPT_WRITEFUNCTION];
                self::assertSame(strlen($xml), $writer(null, $xml));

                return true;
            }
        );

        self::assertIsArray($items);
        self::assertCount(1, $items);
        self::assertSame('Release', (string) $items[0]['title']);
        self::assertSame('https://example.com/release', (string) $items[0]['link']);
    }

    public function testRssRejectsMalformedXml(): void
    {
        $result = App::rss(
            'https://feeds.example/latest.xml',
            static fn (string $host): array => ['93.184.216.34'],
            static function (string $url, array $options): bool {
                $writer = $options[CURLOPT_WRITEFUNCTION];
                $writer(null, '<rss><channel>');
                return true;
            }
        );

        self::assertSame('Invalid RSS', $result);
    }

    public function testRssRemovesActiveFeedContentAndUnsafeUrls(): void
    {
        $xml = '<rss version="2.0"><channel><item>'
            .'<title><![CDATA[<img src=x onerror=alert(1)>Release]]></title>'
            .'<link>javascript:alert(1)</link><image>data:text/html,payload</image>'
            .'<description><![CDATA[<script>alert(1)</script><b>Summary</b>]]></description>'
            .'</item></channel></rss>';

        $items = App::rss(
            'https://feeds.example/latest.xml',
            static fn (string $host): array => ['93.184.216.34'],
            static function (string $url, array $options) use ($xml): bool {
                $writer = $options[CURLOPT_WRITEFUNCTION];
                $writer(null, $xml);
                return true;
            }
        );

        self::assertIsArray($items);
        self::assertSame('Release', $items[0]['title']);
        self::assertNull($items[0]['link']);
        self::assertNull($items[0]['image']);
        self::assertSame('Summary', $items[0]['description']);
    }
}
