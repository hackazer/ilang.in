# NOWPayments Gateway Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add prepaid, email-renewal, and custodial automatic-renewal NOWPayments support to the existing subscription checkout with a dedicated idempotent ledger.

**Architecture:** Register a first-party payment provider that delegates API, signature, state, plan, customer, and reconciliation work to focused classes. Existing billing records remain authoritative while dedicated tables preserve provider lifecycle and replay data.

**Tech Stack:** PHP 8.3 and 8.5, custom GemPixel framework, MySQL, NOWPayments API v1, Bootstrap theme UI, PHPUnit

**Status:** Complete for implementation and automated verification. Live NOWPayments sandbox checkout and IPN delivery remain deployment credentials and provider-environment checks, not local test prerequisites.

---

### Task 1: Add provider value objects and signature verification

**Files:**
- Create: `app/helpers/payments/nowpayments/Signature.php`
- Create: `app/helpers/payments/nowpayments/Status.php`
- Create: `app/helpers/payments/nowpayments/Order.php`
- Create: `tests/Payments/NowPayments/SignatureTest.php`
- Create: `tests/Payments/NowPayments/StatusTest.php`
- Create: `tests/Payments/NowPayments/OrderTest.php`

- [ ] **Step 1: Write failing signature tests**

```php
public function testNestedPayloadIsSortedBeforeSigning(): void
{
    $payload = ['payment_status' => 'finished', 'payment_id' => 42, 'nested' => ['z' => 2, 'a' => 1]];
    $signature = hash_hmac('sha512', '{"nested":{"a":1,"z":2},"payment_id":42,"payment_status":"finished"}', 'secret');
    self::assertTrue(Signature::verify($payload, $signature, 'secret'));
}
```

- [ ] **Step 2: Implement canonical recursive sorting and constant-time verification**

```php
public static function verify(array $payload, string $provided, string $secret): bool
{
    $canonical = self::canonicalJson($payload);
    return hash_equals(hash_hmac('sha512', $canonical, $secret), strtolower($provided));
}
```

- [ ] **Step 3: Implement normalized status and deterministic order IDs**

Map `waiting`, `confirming`, `confirmed`, `sending`, `partially_paid`, `finished`, `failed`, `refunded`, and `expired`. Generate order IDs from user, plan, term, mode, and a cryptographically random attempt ID.

- [ ] **Step 4: Run tests and commit**

Run: `composer test -- --testsuite nowpayments`

```bash
git add app/helpers/payments/nowpayments tests/Payments/NowPayments
git commit -m "feat: add NOWPayments payment primitives"
```

### Task 2: Add the HTTP client and credential boundary

**Files:**
- Create: `app/helpers/payments/nowpayments/Transport.php`
- Create: `app/helpers/payments/nowpayments/Client.php`
- Create: `app/helpers/payments/nowpayments/Credentials.php`
- Create: `tests/Payments/NowPayments/ClientTest.php`
- Create: `tests/Payments/NowPayments/CredentialsTest.php`

- [ ] **Step 1: Write failing transport tests for headers, timeout, retry, and redaction**

```php
public function testApiKeyIsSentAndNeverIncludedInErrors(): void
{
    $transport = new FakeTransport([new TransportResponse(500, [], '{"message":"failure"}')]);
    $client = new Client($transport, 'secret-api-key', 'https://api.nowpayments.io/v1');
    $this->expectExceptionMessage('NOWPayments request failed');
    try {
        $client->status();
    } finally {
        self::assertStringNotContainsString('secret-api-key', $transport->lastSafeError());
    }
}
```

- [ ] **Step 2: Implement the client endpoints**

Implement status, currencies, estimate, minimum amount, create payment, get payment, create invoice, authenticate, create or update subscription plan, create email subscription, create custody customer, create custody deposit, create automatic subscription, list subscriptions, and cancel subscription.

- [ ] **Step 3: Encrypt and validate custody credentials**

Store the dashboard password through `Core\Helper::Encode()` and decrypt only when minting the five-minute JWT. Never render the saved password in admin HTML.

- [ ] **Step 4: Run tests and commit**

Run: `composer test -- --filter 'ClientTest|CredentialsTest'`

```bash
git add app/helpers/payments/nowpayments tests/Payments/NowPayments
git commit -m "feat: add secure NOWPayments API client"
```

### Task 3: Add dedicated database tables and models

**Files:**
- Create: `app/helpers/payments/nowpayments/Migrations.php`
- Create: `app/models/NowPaymentsTransaction.php`
- Create: `app/models/NowPaymentsEvent.php`
- Create: `app/models/NowPaymentsPlan.php`
- Create: `app/models/NowPaymentsCustomer.php`
- Modify: `app/controllers/SetupController.php`
- Modify: `app/controllers/UpdateController.php`
- Create: `tests/Payments/NowPayments/MigrationsTest.php`

- [ ] **Step 1: Write a failing schema-definition test**

```php
public function testMigrationDefinesAllLedgerTables(): void
{
    self::assertSame(
        ['nowpayments_transactions', 'nowpayments_events', 'nowpayments_plans', 'nowpayments_customers'],
        Migrations::tables()
    );
}
```

- [ ] **Step 2: Implement idempotent migrations**

Use `DB::schema()` and existing prefix support. Add unique keys for provider payment ID, order ID, idempotency key, payload hash, plan mapping, and local user customer mapping.

- [ ] **Step 3: Run migration tests and commit**

Run: `composer test -- --filter MigrationsTest`

```bash
git add app/helpers/payments/nowpayments app/models app/controllers/SetupController.php app/controllers/UpdateController.php tests
git commit -m "feat: add NOWPayments ledger schema"
```

### Task 4: Register provider settings and readiness checks

**Files:**
- Create: `app/helpers/payments/NowPayments.php`
- Create: `app/helpers/payments/nowpayments/Readiness.php`
- Modify: `app/traits/Payments.php`
- Modify: `storage/themes/default/admin/settings/payments.php`
- Modify: `storage/themes/ilangin-child/admin/settings/payments.php`
- Create: `tests/Payments/NowPayments/ReadinessTest.php`

- [ ] **Step 1: Write failing readiness tests**

```php
public function testCustodialModeCannotEnableWithoutAllCredentials(): void
{
    $result = Readiness::custodial(['api_key' => 'key', 'ipn_secret' => 'secret']);
    self::assertFalse($result->ready());
    self::assertContains('dashboard_email', $result->missing());
    self::assertContains('dashboard_password', $result->missing());
}
```

- [ ] **Step 2: Register the provider**

Add `nowpayments` to `Traits\Payments` with single and subscription capability, settings, checkout, payment, subscription, webhook, plan synchronization, and cancellation callbacks.

- [ ] **Step 3: Build secure admin settings**

Render enabled modes, prepaid default, environment, API key, masked IPN state, currencies, settlement, partial-payment policy, fee policy, email renewal, and custody readiness. Saved secrets are never echoed back.

- [ ] **Step 4: Run tests and commit**

Run: `composer test -- --filter ReadinessTest`

```bash
git add app/helpers/payments app/traits/Payments.php storage/themes tests
git commit -m "feat: configure NOWPayments payment modes"
```

### Task 5: Implement server-side checkout pricing and prepaid payments

**Files:**
- Create: `app/helpers/payments/nowpayments/Pricing.php`
- Create: `app/helpers/payments/nowpayments/TransactionService.php`
- Modify: `app/helpers/payments/NowPayments.php`
- Modify: `app/controllers/SubscriptionController.php`
- Create: `tests/Payments/NowPayments/PricingTest.php`
- Create: `tests/Payments/NowPayments/PrepaidFlowTest.php`

- [ ] **Step 1: Write failing price and idempotency tests**

```php
public function testBrowserAmountCannotOverrideServerTotal(): void
{
    $total = Pricing::forPlan($this->plan(100), 'monthly', $this->coupon(10), $this->tax(11));
    self::assertSame('99.90', $total->decimal());
}

public function testRepeatedCreationReturnsExistingAttempt(): void
{
    $first = $this->service->createPrepaid($this->request);
    $second = $this->service->createPrepaid($this->request);
    self::assertSame($first->id, $second->id);
}
```

- [ ] **Step 2: Create the local pending records before the remote request**

Calculate price from current plan, coupon, tax, and term. Create a pending local subscription and ledger transaction, then call NOWPayments with the local order ID.

- [ ] **Step 3: Persist the provider response and redirect to local status**

Store provider ID, amount, currency, address, memo or tag, expiration, and sanitized response metadata.

- [ ] **Step 4: Run tests and commit**

Run: `composer test -- --filter 'PricingTest|PrepaidFlowTest'`

```bash
git add app/helpers/payments app/controllers/SubscriptionController.php tests
git commit -m "feat: create prepaid crypto payments"
```

### Task 6: Implement email and custody subscriptions

**Files:**
- Create: `app/helpers/payments/nowpayments/PlanManager.php`
- Create: `app/helpers/payments/nowpayments/CustomerManager.php`
- Create: `app/helpers/payments/nowpayments/SubscriptionService.php`
- Modify: `app/helpers/payments/NowPayments.php`
- Create: `tests/Payments/NowPayments/EmailSubscriptionTest.php`
- Create: `tests/Payments/NowPayments/CustodySubscriptionTest.php`

- [ ] **Step 1: Write failing plan and customer mapping tests**

```php
public function testPlanSyncReusesUnchangedRemotePlan(): void
{
    $first = $this->plans->sync($this->monthlyPlan());
    $second = $this->plans->sync($this->monthlyPlan());
    self::assertSame($first->remoteId(), $second->remoteId());
    self::assertSame(1, $this->client->createPlanCalls());
}
```

- [ ] **Step 2: Implement email enrollment**

Synchronize the remote plan, create the email subscription, and persist the provider subscription ID without granting unpaid entitlement.

- [ ] **Step 3: Implement custody provisioning and automatic enrollment**

Require readiness, provision one sub-partner per local user, expose a custody deposit flow, and create the recurring charge using the remote plan and sub-partner IDs.

- [ ] **Step 4: Run tests and commit**

Run: `composer test -- --filter 'EmailSubscriptionTest|CustodySubscriptionTest'`

```bash
git add app/helpers/payments tests/Payments/NowPayments
git commit -m "feat: add recurring crypto subscription modes"
```

### Task 7: Implement verified idempotent IPN processing

**Files:**
- Create: `app/helpers/payments/nowpayments/WebhookService.php`
- Create: `app/helpers/payments/nowpayments/EntitlementService.php`
- Modify: `app/controllers/WebhookController.php`
- Modify: `app/routes.php`
- Create: `tests/Payments/NowPayments/WebhookTest.php`

- [ ] **Step 1: Write failing webhook tests**

```php
public function testDuplicateFinishedIpnActivatesExactlyOnce(): void
{
    $payload = $this->signedFinishedPayload();
    self::assertSame(200, $this->webhook->handle($payload)->status());
    self::assertSame(200, $this->webhook->handle($payload)->status());
    self::assertSame(1, $this->entitlements->activationCount());
}

public function testInvalidSignatureNeverActivates(): void
{
    self::assertSame(401, $this->webhook->handle($this->payload(), 'invalid')->status());
    self::assertSame(0, $this->entitlements->activationCount());
}
```

- [ ] **Step 2: Add a provider-specific POST callback route**

Register `/webhook/nowpayments` as POST-only and exempt only signature-verified callbacks from CSRF.

- [ ] **Step 3: Process verified events transactionally**

Insert the unique event hash, lock the ledger transaction, validate the transition, create or update local billing records, update user entitlement exactly once, dispatch `payment.success`, and acknowledge duplicates.

- [ ] **Step 4: Run tests and commit**

Run: `composer test -- --filter WebhookTest`

```bash
git add app/helpers/payments app/controllers/WebhookController.php app/routes.php tests
git commit -m "feat: process NOWPayments IPNs safely"
```

### Task 8: Add reconciliation and cancellation

**Files:**
- Create: `app/helpers/payments/nowpayments/Reconciler.php`
- Modify: `app/controllers/CronController.php`
- Modify: `app/helpers/payments/NowPayments.php`
- Create: `tests/Payments/NowPayments/ReconcilerTest.php`

- [ ] **Step 1: Write failing missed-callback and retry tests**

```php
public function testFinishedRemotePaymentIsRecoveredOnce(): void
{
    $this->ledger->pending('provider-42');
    $this->client->willReturnFinished('provider-42');
    $this->reconciler->run(50);
    $this->reconciler->run(50);
    self::assertSame(1, $this->entitlements->activationCount());
}
```

- [ ] **Step 2: Reconcile bounded batches**

Poll only due pending transactions, cap each run, apply exponential backoff with jitter, and stop retrying terminal states.

- [ ] **Step 3: Implement cancellation**

Cancel email or custody subscriptions remotely, mark local subscriptions canceled, and preserve already-paid expiration.

- [ ] **Step 4: Run tests and commit**

Run: `composer test -- --filter ReconcilerTest`

```bash
git add app/helpers/payments app/controllers/CronController.php tests
git commit -m "feat: reconcile and cancel crypto subscriptions"
```

### Task 9: Build accessible checkout and status UI

**Files:**
- Modify: `storage/themes/default/pricing/checkout.php`
- Modify: `storage/themes/ilangin-child/pricing/checkout.php`
- Create: `storage/themes/default/pricing/crypto-status.php`
- Create: `storage/themes/ilangin-child/pricing/crypto-status.php`
- Modify: `public/static/custom.js`
- Modify: `public/static/custom.min.js`
- Create: `tests/Payments/NowPayments/CheckoutMarkupTest.php`

- [ ] **Step 1: Write failing markup assertions**

```php
public function testCryptoControlsHaveLabelsAndStatusLiveRegion(): void
{
    $html = $this->renderCheckout();
    self::assertStringContainsString('for="nowpayments-mode"', $html);
    self::assertStringContainsString('aria-live="polite"', $html);
    self::assertStringContainsString('data-nowpayments-status', $html);
}
```

- [ ] **Step 2: Implement progressive payment-mode controls**

Show only enabled modes, explain prepaid and renewal behavior, reveal custody funding requirements, and preserve keyboard and screen-reader operation.

- [ ] **Step 3: Implement the payment status view**

Display amount, currency, address, memo or tag, QR, expiration, copy controls, and a semantic state timeline. Respect reduced motion.

- [ ] **Step 4: Build minified assets, run tests, and commit**

Run: `composer test -- --filter CheckoutMarkupTest`

```bash
git add storage/themes public/static tests/Payments/NowPayments/CheckoutMarkupTest.php
git commit -m "feat: add accessible crypto checkout UI"
```

### Task 10: Complete gateway verification and documentation

**Files:**
- Create: `docs/nowpayments.md`
- Modify: `README.md` if present
- Update: `.agent/PLANS.md`

- [ ] **Step 1: Document setup and operations**

Document API key, IPN secret, callback URL, sandbox mode, prepaid default, email renewal, custody readiness, cron reconciliation, status semantics, secret rotation, troubleshooting, and rollback.

- [ ] **Step 2: Run complete verification**

Run: `composer validate --strict`

Run: `sh scripts/lint-php.sh`

Run: `composer test`

Run: `composer audit`

Run: `codegraph sync . && codegraph status .`

Expected: all commands exit successfully, CodeGraph is current, and no first-party deprecations are emitted.

- [ ] **Step 3: Review tracked secrets and final diff**

Run: `git grep -n -I -E '(api[_-]?key|secret|password)[[:space:]]*[:=][[:space:]]*[A-Za-z0-9_/-]{16,}' -- ':!composer.lock'`

Run: `git diff --check && git diff --stat main...dev`

- [ ] **Step 4: Commit documentation**

```bash
git add docs .agent/PLANS.md
git commit -m "docs: add NOWPayments operations runbook"
```
