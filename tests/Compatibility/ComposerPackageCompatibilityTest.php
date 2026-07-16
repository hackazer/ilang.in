<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Abraham\TwitterOAuth\TwitterOAuth;
use Endroid\QrCode\Writer\SvgWriter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use PHPMailer\PHPMailer\PHPMailer;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use PHPUnit\Framework\TestCase;
use Stripe\StripeClient;

final class ComposerPackageCompatibilityTest extends TestCase
{
    public function testTwitterOauthProvidesTheApplicationEntryPoints(): void
    {
        $client = new TwitterOAuth('consumer-key', 'consumer-secret', 'token', 'token-secret');

        self::assertTrue(method_exists($client, 'oauth'));
        self::assertTrue(method_exists($client, 'url'));
        self::assertTrue(method_exists($client, 'getLastHttpCode'));
    }

    public function testQrHelperGeneratesAnSvgDataUri(): void
    {
        require_once dirname(__DIR__, 2).'/app/helpers/QrGd.php';

        $uri = (new \Helpers\QRGd('composer-compatibility', 120, 5))
            ->color('rgb(10,20,30)', 'rgb(240,245,250)')
            ->format('svg')
            ->create('uri');

        self::assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        self::assertTrue(class_exists(SvgWriter::class));
    }

    public function testQrHelperFallsBackToSafeColorsForInvalidCssValues(): void
    {
        require_once dirname(__DIR__, 2).'/app/helpers/QrGd.php';

        $uri = (new \Helpers\QRGd('composer-color-fallback', 120, 5))
            ->color('invalid', 'invalid')
            ->format('svg')
            ->create('uri');

        self::assertStringStartsWith('data:image/svg+xml;base64,', $uri);
    }

    public function testMonologAcceptsTypedLevelsUsedByTheApplication(): void
    {
        $handler = new StreamHandler('php://memory', Level::Error);

        self::assertSame(Level::Error, $handler->getLevel());
    }

    public function testPhpFastCacheFilesDriverRoundTripsValues(): void
    {
        $path = sys_get_temp_dir().'/ilang-composer-cache-'.bin2hex(random_bytes(8));
        $cache = CacheManager::getInstance('files', new ConfigurationOption(['path' => $path]));
        $item = $cache->getItem('compatibility-key');
        $item->set('compatible');

        self::assertTrue($cache->save($item));
        self::assertSame('compatible', $cache->getItem('compatibility-key')->get());

        $cache->clear();
        @rmdir($path);
    }

    public function testPhpmailerRetainsTheConfiguredTransportApi(): void
    {
        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->setFrom('sender@example.com', 'Sender');
        $mailer->addAddress('recipient@example.com', 'Recipient');

        self::assertSame('smtp', $mailer->Mailer);
        self::assertSame('sender@example.com', $mailer->From);
        self::assertSame('Sender', $mailer->FromName);
        self::assertSame([['recipient@example.com', 'Recipient']], $mailer->getToAddresses());
    }

    public function testStripeClientExposesEveryServiceUsedByTheApplication(): void
    {
        $client = new StripeClient('sk_test_composer_compatibility');

        self::assertNotNull($client->customers);
        self::assertNotNull($client->invoices);
        self::assertNotNull($client->invoicePayments);
        self::assertNotNull($client->subscriptions);
        self::assertNotNull($client->products);
        self::assertNotNull($client->plans);
        self::assertNotNull($client->refunds);
        self::assertNotNull($client->coupons);
        self::assertNotNull($client->taxRates);
    }
}
