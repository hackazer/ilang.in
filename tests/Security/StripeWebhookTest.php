<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\Payments\Stripe;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\SignatureVerificationException;

require_once dirname(__DIR__, 2).'/app/helpers/payments/Stripe.php';

final class StripeWebhookTest extends TestCase
{
    private int|false $previousStatus;

    protected function setUp(): void
    {
        $this->previousStatus = http_response_code();
    }

    protected function tearDown(): void
    {
        http_response_code($this->previousStatus ?: 200);
    }

    public function testWebhookRejectsNonPostRequestsBeforeReadingConfiguration(): void
    {
        Stripe::webhook(new StripeWebhookRequest(false));

        self::assertSame(405, http_response_code());
    }

    public function testWebhookValidationFailsClosedWithoutSigningSecret(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('signing secret');

        Stripe::verifiedWebhookEvent($this->payload(), 't=1,v1=invalid', '');
    }

    public function testWebhookValidationRejectsInvalidSignature(): void
    {
        $this->expectException(SignatureVerificationException::class);

        Stripe::verifiedWebhookEvent($this->payload(), 't='.time().',v1=invalid', 'whsec_test');
    }

    public function testWebhookValidationReturnsVerifiedEventAndProviderIds(): void
    {
        $payload = $this->payload();
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $event = Stripe::verifiedWebhookEvent(
            $payload,
            't='.$timestamp.',v1='.$signature,
            'whsec_test'
        );

        self::assertSame(
            ['event_id' => 'evt_security_1', 'object_id' => 'ch_security_1'],
            Stripe::webhookIdentity($event)
        );
    }

    public function testProviderEventAndObjectIdsAreBothCheckedForIdempotency(): void
    {
        $lookups = [];

        $duplicate = Stripe::webhookAlreadyProcessed(
            'evt_security_1',
            'ch_security_1',
            static function (string $column, string $value) use (&$lookups): bool {
                $lookups[] = [$column, $value];

                return $column === 'cid';
            }
        );

        self::assertTrue($duplicate);
        self::assertSame([
            ['tid', 'evt_security_1'],
            ['cid', 'ch_security_1'],
        ], $lookups);
    }

    public function testOnlyChargeOutcomeEventsCanChangeBillingState(): void
    {
        self::assertTrue(Stripe::isSupportedWebhookEventType('charge.succeeded'));
        self::assertTrue(Stripe::isSupportedWebhookEventType('charge.failed'));
        self::assertFalse(Stripe::isSupportedWebhookEventType('charge.refunded'));
        self::assertFalse(Stripe::isSupportedWebhookEventType('customer.updated'));
    }

    public function testWebhookLockNamesAreStableAndProviderScoped(): void
    {
        $first = Stripe::webhookLockName('evt_security_1', 'ch_security_1');

        self::assertSame($first, Stripe::webhookLockName('evt_security_1', 'ch_security_1'));
        self::assertNotSame($first, Stripe::webhookLockName('evt_security_2', 'ch_security_1'));
        self::assertLessThanOrEqual(64, strlen($first));
    }

    private function payload(): string
    {
        return json_encode([
            'id' => 'evt_security_1',
            'object' => 'event',
            'type' => 'charge.succeeded',
            'created' => time(),
            'data' => [
                'object' => [
                    'id' => 'ch_security_1',
                    'object' => 'charge',
                    'customer' => 'cus_security_1',
                    'paid' => true,
                    'status' => 'succeeded',
                    'amount' => 1200,
                ],
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}

final class StripeWebhookRequest
{
    public function __construct(private readonly bool $post)
    {
    }

    public function isPost(): bool
    {
        return $this->post;
    }
}
