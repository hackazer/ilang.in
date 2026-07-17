<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\Payments\IpnListener;
use Helpers\Payments\Paypal;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

require_once dirname(__DIR__, 2).'/app/helpers/payments/IpnListener.php';
require_once dirname(__DIR__, 2).'/app/helpers/payments/Paypal.php';

final class PaypalIpnTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testListenerRequiresPostRequests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectException(Exception::class);

        (new IpnListener())->requirePostMethod();
    }

    public function testListenerAcceptsOnlyAnExactVerifiedResponseBody(): void
    {
        $listener = new StubIpnListener(
            '200',
            "HTTP/1.1 200 OK\r\nX-Fake: VERIFIED\r\n\r\nINVALID\r\n"
        );

        self::assertFalse($listener->processIpn(['txn_id' => 'PAY-1']));
    }

    public function testListenerAcceptsExactVerifiedResponseBody(): void
    {
        $listener = new StubIpnListener('200', "HTTP/1.1 200 OK\r\n\r\nVERIFIED\r\n");

        self::assertTrue($listener->processIpn(['txn_id' => 'PAY-1']));
    }

    public function testCurlVerificationUsesTlsPeerAndHostChecks(): void
    {
        if (!defined('STORAGE')) {
            define('STORAGE', dirname(__DIR__, 2).'/storage');
        }

        $options = (new InspectableIpnListener())->options('https://www.paypal.com/cgi-bin/webscr', 'payload');

        self::assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        self::assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        self::assertSame(CURLPROTO_HTTPS, $options[CURLOPT_PROTOCOLS]);
        self::assertSame(CURLPROTO_HTTPS, $options[CURLOPT_REDIR_PROTOCOLS]);
        self::assertArrayNotHasKey(CURLOPT_CAINFO, $options, 'The bundled CA certificate expired in 2011.');
    }

    public function testCompletedIpnReturnsServerDerivedPlanAmount(): void
    {
        $amount = Paypal::validateCompletedIpn(
            $this->validPayload(),
            $this->plan(),
            'merchant@example.com',
            'USD',
            'Monthly'
        );

        self::assertSame('10.00', $amount);
    }

    public function testCheckoutPricingContextAppliesCouponThenTax(): void
    {
        $this->assertPricingContextApiExists();

        $context = Paypal::createPricingContext(
            $this->plan(),
            'Monthly',
            (object) ['discount' => '10'],
            (object) ['rate' => '11'],
            'usd',
            42,
            false,
            'checkout-secret'
        );

        self::assertSame('9.99', $context['amount']);
        self::assertSame('USD', $context['currency']);
        self::assertSame(42, $context['userid']);
        self::assertSame(7, $context['planid']);
        self::assertSame('Monthly', $context['period']);
        self::assertSame(0, $context['renew']);
        self::assertNotSame('', $context['signature']);
        self::assertLessThanOrEqual(256, strlen(json_encode($context, JSON_THROW_ON_ERROR)));
    }

    public function testCompletedIpnUsesTheSignedCheckoutAmountAndCurrency(): void
    {
        $this->assertPricingContextApiExists();

        $context = Paypal::createPricingContext(
            $this->plan(),
            'Monthly',
            (object) ['discount' => '10'],
            (object) ['rate' => '11'],
            'USD',
            42,
            false,
            'checkout-secret'
        );
        $plan = $this->plan();
        $plan->price_monthly = '25.00';
        $payload = array_replace($this->validPayload(), [
            'mc_gross' => '9.99',
            'mc_currency' => 'USD',
        ]);

        self::assertSame(
            '9.99',
            Paypal::validateCompletedIpn(
                $payload,
                $plan,
                'merchant@example.com',
                'EUR',
                'Monthly',
                $context,
                'checkout-secret'
            )
        );
    }

    public function testTamperedCheckoutPricingContextIsRejected(): void
    {
        $this->assertPricingContextApiExists();

        $context = Paypal::createPricingContext(
            $this->plan(),
            'Monthly',
            null,
            null,
            'USD',
            42,
            false,
            'checkout-secret'
        );
        $context['amount'] = '1.00';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pricing context');

        Paypal::validateCompletedIpn(
            array_replace($this->validPayload(), ['mc_gross' => '1.00']),
            $this->plan(),
            'merchant@example.com',
            'USD',
            'Monthly',
            $context,
            'checkout-secret'
        );
    }

    public function testLifetimeEntitlementKeepsTheTwentyYearSentinel(): void
    {
        self::assertTrue(
            method_exists(Paypal::class, 'entitlementWindow'),
            'PayPal Basic must expose its entitlement window for sentinel verification.'
        );

        self::assertSame(
            ['modifier' => '+ 20 years', 'duration' => '20 Years'],
            Paypal::entitlementWindow('Lifetime')
        );
    }

    #[DataProvider('invalidPayloads')]
    public function testInvalidCompletedIpnIsRejected(array $overrides, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Paypal::validateCompletedIpn(
            array_replace($this->validPayload(), $overrides),
            $this->plan(),
            'merchant@example.com',
            'USD',
            'Monthly'
        );
    }

    public static function invalidPayloads(): array
    {
        return [
            'pending payment' => [['payment_status' => 'Pending'], 'not completed'],
            'wrong receiver' => [['receiver_email' => 'attacker@example.com'], 'receiver'],
            'wrong currency' => [['mc_currency' => 'EUR'], 'currency'],
            'callback amount below plan price' => [['mc_gross' => '1.00'], 'amount'],
            'missing transaction id' => [['txn_id' => ''], 'transaction ID'],
        ];
    }

    public function testTermSelectsTheMatchingServerSidePlanPrice(): void
    {
        $payload = array_replace($this->validPayload(), ['mc_gross' => '100.00']);

        self::assertSame(
            '100.00',
            Paypal::validateCompletedIpn($payload, $this->plan(), 'merchant@example.com', 'USD', 'Yearly')
        );
    }

    public function testEmptyConfiguredCurrencyIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency');

        Paypal::validateCompletedIpn(
            array_replace($this->validPayload(), ['mc_currency' => '']),
            $this->plan(),
            'merchant@example.com',
            '',
            'Monthly'
        );
    }

    private function validPayload(): array
    {
        return [
            'payment_status' => 'Completed',
            'receiver_email' => 'Merchant@example.com',
            'mc_currency' => 'USD',
            'mc_gross' => '10.00',
            'txn_id' => 'PAY-1',
        ];
    }

    private function assertPricingContextApiExists(): void
    {
        self::assertTrue(
            method_exists(Paypal::class, 'createPricingContext'),
            'PayPal Basic must derive an immutable checkout pricing context.'
        );
    }

    private function plan(): object
    {
        return (object) [
            'id' => 7,
            'price_monthly' => '10.00',
            'price_yearly' => '100.00',
            'price_lifetime' => '300.00',
        ];
    }
}

final class StubIpnListener extends IpnListener
{
    public function __construct(private readonly string $status, private readonly string $body)
    {
    }

    protected function curlPost($encoded_data)
    {
        $status = new ReflectionProperty(IpnListener::class, 'response_status');
        $status->setValue($this, $this->status);

        $response = new ReflectionProperty(IpnListener::class, 'response');
        $response->setValue($this, $this->body);
    }
}

final class InspectableIpnListener extends IpnListener
{
    public function options(string $uri, string $data): array
    {
        return $this->curlOptions($uri, $data);
    }
}
