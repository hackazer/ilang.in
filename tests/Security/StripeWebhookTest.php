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
        self::assertTrue(Stripe::isSupportedWebhookEventType('charge.refunded'));
        self::assertTrue(Stripe::isSupportedWebhookEventType('charge.refund.updated'));
        self::assertTrue(Stripe::isSupportedWebhookEventType('charge.dispute.funds_withdrawn'));
        self::assertTrue(Stripe::isSupportedWebhookEventType('charge.dispute.funds_reinstated'));
        self::assertTrue(Stripe::isSupportedWebhookEventType('checkout.session.completed'));
        self::assertTrue(Stripe::isSupportedWebhookEventType('checkout.session.async_payment_succeeded'));
        self::assertFalse(Stripe::isSupportedWebhookEventType('charge.dispute.updated'));
        self::assertFalse(Stripe::isSupportedWebhookEventType('customer.updated'));
    }

    public function testRefundEventsNormalizeCumulativeLossAndFailedRefundReversal(): void
    {
        $refund = $this->event('charge.refunded', [
            'id' => 'ch_refund_1',
            'object' => 'charge',
            'amount' => 1200,
            'amount_refunded' => 500,
            'currency' => 'usd',
        ]);
        $reversal = $this->event('charge.refund.updated', [
            'id' => 're_refund_1',
            'object' => 'refund',
            'charge' => 'ch_refund_1',
            'amount' => 500,
            'currency' => 'usd',
            'status' => 'failed',
        ]);

        self::assertSame([
            'kind' => 'refund',
            'charge_id' => 'ch_refund_1',
            'provider_object_id' => 'ch_refund_1',
            'amount_minor' => 500,
            'currency' => 'USD',
            'occurred_at' => 1_750_000_000,
        ], Stripe::normalizeNegativeWebhookEvent($refund));
        self::assertSame([
            'kind' => 'refund_reversed',
            'charge_id' => 'ch_refund_1',
            'provider_object_id' => 're_refund_1',
            'amount_minor' => 500,
            'currency' => 'USD',
            'occurred_at' => 1_750_000_000,
        ], Stripe::normalizeNegativeWebhookEvent($reversal));

        $pending = $this->event('charge.refund.updated', [
            'id' => 're_refund_2',
            'object' => 'refund',
            'charge' => 'ch_refund_1',
            'amount' => 500,
            'currency' => 'usd',
            'status' => 'pending',
        ]);
        self::assertNull(Stripe::normalizeNegativeWebhookEvent($pending));
    }

    public function testDisputeFundMovementsNormalizeWithoutTreatingUpdatesAsMoneyMovement(): void
    {
        $withdrawn = $this->event('charge.dispute.funds_withdrawn', [
            'id' => 'du_security_1',
            'object' => 'dispute',
            'charge' => 'ch_security_1',
            'amount' => 1200,
            'currency' => 'usd',
            'status' => 'needs_response',
        ]);
        $reinstated = $this->event('charge.dispute.funds_reinstated', [
            'id' => 'du_security_1',
            'object' => 'dispute',
            'charge' => 'ch_security_1',
            'amount' => 1200,
            'currency' => 'usd',
            'status' => 'won',
        ]);

        self::assertSame('dispute', Stripe::normalizeNegativeWebhookEvent($withdrawn)['kind']);
        self::assertSame('dispute_reversed', Stripe::normalizeNegativeWebhookEvent($reinstated)['kind']);
        self::assertSame('du_security_1', Stripe::normalizeNegativeWebhookEvent($withdrawn)['provider_object_id']);
        self::assertNull(Stripe::normalizeNegativeWebhookEvent(
            $this->event('charge.dispute.updated', (array) $withdrawn->data->object)
        ));
    }

    public function testNegativeLedgerDeltasAreCumulativeForRefundsAndCappedForReversals(): void
    {
        self::assertSame(-300, Stripe::negativeAdjustmentMinor('refund', 500, 200, 0, 1200));
        self::assertSame(0, Stripe::negativeAdjustmentMinor('refund', 500, 500, 0, 1200));
        self::assertSame(-300, Stripe::negativeAdjustmentMinor('refund', 800, 500, 0, 1200));
        self::assertSame(300, Stripe::negativeAdjustmentMinor('refund_reversed', 500, 300, 0, 1200));
        self::assertSame(-1000, Stripe::negativeAdjustmentMinor('dispute', 1200, 200, 0, 1200));
        self::assertSame(700, Stripe::negativeAdjustmentMinor('dispute_reversed', 900, 0, 700, 1200));
    }

    public function testCumulativeChargeRefundEventsDoNotUseChargeIdAsTheDeduplicationKey(): void
    {
        self::assertFalse(Stripe::negativeProviderObjectDedupSafe('charge.refunded'));
        self::assertTrue(Stripe::negativeProviderObjectDedupSafe('refund.updated'));
    }

    public function testOnlyFullLossOfTheCurrentStripePaymentCanChangeEntitlement(): void
    {
        self::assertFalse(Stripe::negativeEventAffectsCurrentEntitlement(
            500,
            1200,
            7,
            7,
            '2026-08-17 00:00:00',
            '2026-08-17 00:00:00'
        ));
        self::assertTrue(Stripe::negativeEventAffectsCurrentEntitlement(
            1200,
            1200,
            7,
            7,
            '2026-08-17 00:00:00',
            '2026-08-17 00:00:00'
        ));
        self::assertFalse(Stripe::negativeEventAffectsCurrentEntitlement(
            1200,
            1200,
            7,
            7,
            '2026-09-17 00:00:00',
            '2026-08-17 00:00:00'
        ));
        self::assertFalse(Stripe::negativeEventAffectsCurrentEntitlement(
            1200,
            1200,
            9,
            7,
            '2026-08-17 00:00:00',
            '2026-08-17 00:00:00'
        ));
    }

    public function testAnotherActivePaidSubscriptionPreventsRevocation(): void
    {
        $subscriptions = [
            (object) ['id' => 10, 'planid' => 7, 'status' => 'Refunded', 'amount' => 0, 'expiry' => '2026-08-17 00:00:00'],
            (object) ['id' => 11, 'planid' => 8, 'status' => 'Active', 'amount' => 25, 'expiry' => '2026-09-17 00:00:00'],
            (object) ['id' => 12, 'planid' => 9, 'status' => 'Active', 'amount' => 40, 'expiry' => '2026-10-17 00:00:00'],
            (object) ['id' => 13, 'planid' => 10, 'status' => 'Active', 'amount' => 0, 'expiry' => '2027-01-01 00:00:00'],
        ];

        self::assertSame([
            'subscription_id' => 12,
            'plan_id' => 9,
            'expiration' => '2026-10-17 00:00:00',
        ], Stripe::activePaidEntitlement($subscriptions, 10, strtotime('2026-07-17 00:00:00')));
        self::assertNull(Stripe::activePaidEntitlement([$subscriptions[0], $subscriptions[3]], 10));
    }

    public function testPaidCheckoutSessionCarriesTrustedLocalBillingContext(): void
    {
        $paid = $this->event('checkout.session.completed', [
            'id' => 'cs_security_1',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'mode' => 'payment',
            'customer' => 'cus_security_1',
            'payment_intent' => 'pi_security_1',
            'amount_total' => 1200,
            'currency' => 'usd',
            'metadata' => [
                'local_subscription_id' => '41',
                'local_subscription_uniqueid' => 'local-security-1',
            ],
        ]);

        self::assertSame([
            'session_id' => 'cs_security_1',
            'payment_intent_id' => 'pi_security_1',
            'charge_id' => '',
            'invoice_id' => '',
            'mode' => 'payment',
            'provider_payment_id' => 'pi_security_1',
            'customer_id' => 'cus_security_1',
            'subscription_id' => 41,
            'subscription_uniqueid' => 'local-security-1',
            'amount_minor' => 1200,
            'currency' => 'USD',
            'occurred_at' => 1_750_000_000,
        ], Stripe::checkoutSessionPaymentContext($paid));

        $unpaid = $this->event('checkout.session.completed', [
            'id' => 'cs_security_2',
            'object' => 'checkout.session',
            'payment_status' => 'unpaid',
        ]);
        self::assertNull(Stripe::checkoutSessionPaymentContext($unpaid));
    }

    public function testCheckoutContextKeepsSessionPaymentIntentAndChargeMappingsSeparate(): void
    {
        $paid = $this->event('checkout.session.completed', [
            'id' => 'cs_mapping_1',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'mode' => 'payment',
            'customer' => 'cus_mapping_1',
            'payment_intent' => (object) [
                'id' => 'pi_mapping_1',
                'latest_charge' => (object) ['id' => 'ch_mapping_1'],
            ],
            'metadata' => [
                'local_subscription_id' => '41',
                'local_subscription_uniqueid' => 'local-mapping-1',
            ],
            'amount_total' => 1200,
            'currency' => 'usd',
        ]);

        self::assertSame([
            'session_id' => 'cs_mapping_1',
            'payment_intent_id' => 'pi_mapping_1',
            'charge_id' => 'ch_mapping_1',
            'invoice_id' => '',
        ], array_intersect_key(
            Stripe::checkoutSessionPaymentContext($paid),
            array_flip(['session_id', 'payment_intent_id', 'charge_id', 'invoice_id'])
        ));
    }

    public function testChargeReconciliationUsesCanonicalProviderMappingAndDoesNotRejectAValidLaterCharge(): void
    {
        self::assertTrue(Stripe::providerIdentityMatches([
            'session_id' => 'cs_mapping_1',
            'payment_intent_id' => 'pi_mapping_1',
            'charge_id' => 'ch_mapping_1',
        ], 'ch_mapping_1'));

        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Stripe.php');

        self::assertIsString($source);
        self::assertStringContainsString('findSubscriptionByStripeProviderMapping', $source);
        self::assertStringContainsString("'stripe_charge_id'", $source);
        self::assertStringContainsString("'stripe_payment_intent_id'", $source);
        self::assertStringContainsString('Stripe charge does not match its canonical local checkout mapping.', $this->methodSource($source, 'resolveWebhookSubscription'));
    }

    public function testModernRefundObjectEventsAreSupported(): void
    {
        self::assertTrue(Stripe::isSupportedWebhookEventType('refund.failed'));
        self::assertTrue(Stripe::isSupportedWebhookEventType('refund.updated'));

        $failed = $this->event('refund.failed', [
            'id' => 're_modern_1',
            'object' => 'refund',
            'charge' => 'ch_modern_1',
            'amount' => 500,
            'currency' => 'usd',
            'status' => 'failed',
        ]);

        self::assertSame('refund_reversed', Stripe::normalizeNegativeWebhookEvent($failed)['kind']);
        self::assertSame('ch_modern_1', Stripe::normalizeNegativeWebhookEvent($failed)['charge_id']);
    }

    public function testOutOfOrderNegativeEventsArePersistedAndReplayedAfterDebit(): void
    {
        self::assertSame([
            'event_id' => 'evt_pending_1',
            'charge_id' => 'ch_pending_1',
            'kind' => 'refund',
            'provider_object_id' => 're_pending_1',
            'amount_minor' => 500,
            'currency' => 'USD',
        ], Stripe::pendingNegativeWebhookState([
            'kind' => 'refund',
            'charge_id' => 'ch_pending_1',
            'provider_object_id' => 're_pending_1',
            'amount_minor' => 500,
            'currency' => 'USD',
        ], 'evt_pending_1'));

        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Stripe.php');

        self::assertIsString($source);
        self::assertStringContainsString('stripe_pending_negative', $source);
        self::assertStringContainsString('replayPendingNegativeEvents', $source);
        self::assertStringContainsString("JSON_EXTRACT(`data`, '$.pending_action.charge_id')", $source);
        self::assertStringNotContainsString('$pending->cid = $action[\'charge_id\'];', $source);
        self::assertStringNotContainsString('return 409;', $this->methodSource($source, 'handleNegativeWebhookEvent'));
    }

    public function testCouponCapacityIsReservedBeforeStripeCheckoutCreation(): void
    {
        self::assertTrue(Stripe::couponHasCapacity(4, 5, 0));
        self::assertFalse(Stripe::couponHasCapacity(4, 5, 1));
        self::assertTrue(Stripe::couponHasCapacity(5, 0, 100));

        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Stripe.php');

        self::assertIsString($source);
        self::assertStringContainsString('acquireStripeCouponLock', $source);
        self::assertStringContainsString('pendingCouponReservations', $source);
        self::assertStringContainsString('$sub->status = "Pending"', $this->methodSource($source, 'paymentLink'));
        self::assertStringContainsString("'Canceled'", $this->methodSource($source, 'paymentLink'));
    }

    public function testDuplicateCheckoutSuccessReturnsOkAfterMappingAndFulfillment(): void
    {
        self::assertSame(200, Stripe::checkoutSuccessResponse(true));

        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Stripe.php');

        self::assertIsString($source);
        $handler = $this->methodSource($source, 'handleCheckoutSessionPayment');
        self::assertStringContainsString('providerMappingAlreadyProcessed', $handler);
        self::assertStringNotContainsString('return 409;', $handler);
    }

    public function testZeroTotalCheckoutSessionNeedsNoPaymentIntent(): void
    {
        $free = $this->event('checkout.session.completed', [
            'id' => 'cs_zero_1',
            'object' => 'checkout.session',
            'payment_status' => 'no_payment_required',
            'mode' => 'payment',
            'customer' => 'cus_zero_1',
            'metadata' => [
                'local_subscription_id' => '42',
                'local_subscription_uniqueid' => 'local-zero-1',
            ],
            'amount_total' => 0,
            'currency' => 'jpy',
        ]);

        $context = Stripe::checkoutSessionPaymentContext($free);

        self::assertNotNull($context);
        self::assertSame('cs_zero_1', $context['session_id']);
        self::assertSame(0, $context['amount_minor']);
        self::assertSame('JPY', $context['currency']);
    }

    public function testAggregateSubscriptionStatusPreservesEntitlementForPositiveRemainingBalance(): void
    {
        self::assertSame('Active', Stripe::aggregateSubscriptionStatus(500, 1200, 0));
        self::assertSame('Disputed', Stripe::aggregateSubscriptionStatus(0, 1200, 0));
        self::assertSame('Refunded', Stripe::aggregateSubscriptionStatus(0, 0, 1200));
    }

    public function testCurrencyAwareMinorUnitsHandleJpyAndStripeDecimalCurrencies(): void
    {
        self::assertSame(1, Stripe::currencyMinorUnit('JPY'));
        self::assertSame(100, Stripe::currencyMinorUnit('USD'));
        self::assertSame(1200, Stripe::toMinorUnits('1200', 'JPY'));
        self::assertSame(1200, Stripe::toMinorUnits('12', 'USD'));
        self::assertSame(1200, Stripe::fromMinorUnits(1200, 'JPY'));
        self::assertSame(12, Stripe::fromMinorUnits(1200, 'USD'));
    }

    public function testLifetimePaymentAndWebhookShareCanonicalChargeIdentity(): void
    {
        self::assertTrue(Stripe::providerIdentityMatches([
            'session_id' => '',
            'payment_intent_id' => 'pi_lifetime_1',
            'charge_id' => 'ch_lifetime_1',
        ], 'ch_lifetime_1'));

        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Stripe.php');

        self::assertIsString($source);
        self::assertStringContainsString('providerMappingAlreadyProcessed', $source);
        self::assertStringContainsString("'stripe_charge_id'", $source);
        self::assertStringContainsString("'stripe_payment_intent_id'", $source);
    }

    public function testRecurringCheckoutDefersFulfillmentToTheExistingChargeSuccessPath(): void
    {
        self::assertFalse(Stripe::checkoutSessionRequiresImmediateFulfillment('subscription'));
        self::assertTrue(Stripe::checkoutSessionRequiresImmediateFulfillment('payment'));
    }

    public function testCouponUseIsConsumedExactlyOnceAfterConfirmedStripeSuccess(): void
    {
        $subscription = new StripeWebhookRecord([
            'data' => json_encode([
                'paymentmethod' => 'Stripe',
                'coupon_id' => 17,
            ], JSON_THROW_ON_ERROR),
        ]);
        $coupon = new StripeWebhookRecord(['id' => 17, 'used' => 2]);
        $lookups = 0;

        self::assertTrue(Stripe::consumeCouponForSubscription(
            $subscription,
            static function (int $id) use ($coupon, &$lookups): object {
                $lookups++;
                self::assertSame(17, $id);
                return $coupon;
            },
            static fn (): string => '2026-07-17 12:00:00'
        ));
        self::assertFalse(Stripe::consumeCouponForSubscription($subscription));

        self::assertSame(3, $coupon->used);
        self::assertSame(1, $coupon->saves);
        self::assertSame(1, $lookups);
        self::assertSame(1, $subscription->saves);
        self::assertSame('2026-07-17 12:00:00', json_decode($subscription->data, true)['coupon_consumed_at']);
    }

    public function testStripeCheckoutDefersCouponConsumptionUntilProviderSuccess(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/payments/Stripe.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('$coupon->used++;', $source);
        self::assertStringContainsString('consumeCouponForSubscription($sub)', $this->methodSource($source, 'payment'));
        self::assertStringContainsString('handleCheckoutSessionPayment($e, $identity)', $this->methodSource($source, 'webhook'));
        self::assertStringContainsString("'local_subscription_id'", $this->methodSource($source, 'paymentLink'));
    }

    public function testWebhookLockNamesAreStableAndProviderScoped(): void
    {
        $first = Stripe::webhookLockName('evt_security_1', 'ch_security_1');

        self::assertSame($first, Stripe::webhookLockName('evt_security_1', 'ch_security_1'));
        self::assertNotSame($first, Stripe::webhookLockName('evt_security_2', 'ch_security_1'));
        self::assertLessThanOrEqual(64, strlen($first));
    }

    public function testOnlyActiveCheckoutSubscriptionsCanGrantImmediateAccess(): void
    {
        self::assertTrue(Stripe::subscriptionGrantsEntitlement('active'));
        self::assertFalse(Stripe::subscriptionGrantsEntitlement('incomplete'));
        self::assertFalse(Stripe::subscriptionGrantsEntitlement('past_due'));
        self::assertFalse(Stripe::subscriptionGrantsEntitlement('canceled'));
    }

    public function testWebhookChargeMustMatchProviderBillingContext(): void
    {
        self::assertTrue(Stripe::webhookChargeContextIsValid(1200, 'usd', 1200, 'USD'));
        self::assertFalse(Stripe::webhookChargeContextIsValid(1199, 'usd', 1200, 'USD'));
        self::assertFalse(Stripe::webhookChargeContextIsValid(1200, 'eur', 1200, 'USD'));
    }

    public function testInvoiceSubscriptionIdSupportsCurrentAndLegacyStripeShapes(): void
    {
        $current = (object) [
            'parent' => (object) [
                'subscription_details' => (object) ['subscription' => 'sub_current'],
            ],
        ];
        $legacy = (object) ['subscription' => 'sub_legacy'];

        self::assertSame('sub_current', Stripe::invoiceSubscriptionId($current));
        self::assertSame('sub_legacy', Stripe::invoiceSubscriptionId($legacy));
    }

    public function testInvoicePaymentContextSupportsCurrentStripeShape(): void
    {
        $invoicePayment = (object) [
            'invoice' => 'in_current',
            'payment' => (object) [
                'charge' => 'ch_current',
                'payment_intent' => 'pi_current',
            ],
        ];

        self::assertSame([
            'invoice_id' => 'in_current',
            'charge_id' => 'ch_current',
            'payment_intent_id' => 'pi_current',
        ], Stripe::invoicePaymentContext($invoicePayment));
    }

    public function testSubscriptionBillingContextSupportsCurrentAndLegacyStripeShapes(): void
    {
        $current = (object) [
            'items' => (object) [
                'data' => [
                    (object) [
                        'current_period_start' => 100,
                        'current_period_end' => 200,
                        'price' => (object) [
                            'recurring' => (object) ['interval' => 'year'],
                        ],
                    ],
                ],
            ],
        ];
        $legacy = (object) [
            'current_period_start' => 300,
            'current_period_end' => 400,
            'plan' => (object) ['interval' => 'month'],
        ];

        self::assertSame([
            'interval' => 'year',
            'start' => 100,
            'end' => 200,
        ], Stripe::subscriptionBillingContext($current));
        self::assertSame([
            'interval' => 'month',
            'start' => 300,
            'end' => 400,
        ], Stripe::subscriptionBillingContext($legacy));
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

    /** @param array<string, mixed> $object */
    private function event(string $type, array $object): \Stripe\Event
    {
        return \Stripe\Event::constructFrom([
            'id' => 'evt_'.str_replace('.', '_', $type),
            'object' => 'event',
            'type' => $type,
            'created' => 1_750_000_000,
            'data' => ['object' => $object],
        ]);
    }

    private function methodSource(string $source, string $method): string
    {
        $start = strpos($source, 'function '.$method.'(');
        self::assertNotFalse($start, $method);
        $brace = strpos($source, '{', $start);
        self::assertNotFalse($brace, $method);
        $depth = 0;
        $length = strlen($source);

        for ($offset = $brace; $offset < $length; $offset++) {
            if ($source[$offset] === '{') {
                $depth++;
            } elseif ($source[$offset] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $start, $offset - $start + 1);
                }
            }
        }

        self::fail('Could not isolate '.$method.'.');
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

final class StripeWebhookRecord
{
    public int $saves = 0;
    public mixed $data = null;
    public mixed $id = null;
    public mixed $used = null;
    public mixed $coupon = null;

    /** @param array<string, mixed> $values */
    public function __construct(array $values)
    {
        foreach ($values as $property => $value) {
            $this->{$property} = $value;
        }
    }

    public function save(): void
    {
        $this->saves++;
    }
}
