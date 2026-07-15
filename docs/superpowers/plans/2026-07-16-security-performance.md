# Security and Performance Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Produce an evidence-backed audit and fix high-confidence security and performance defects that affect authentication, billing, redirects, uploads, webhooks, and PHP 8.5 operation.

**Architecture:** Add repeatable audit commands and focused regression tests. Fix defects at existing boundaries instead of rewriting the custom framework.

**Tech Stack:** PHP 8.3 and 8.5, PHPUnit, Composer audit, custom GemPixel framework

---

### Task 1: Create a reproducible audit report

**Files:**
- Create: `docs/audits/2026-07-16-security-performance.md`
- Create: `scripts/audit-source.sh`

- [ ] **Step 1: Add deterministic source checks**

```sh
#!/bin/sh
set -eu
rg -n --glob '*.php' --glob '!vendor/**' 'unserialize\(|eval\(|shell_exec\(|exec\(|system\(|passthru\(' . || true
rg -n --glob '*.php' --glob '!vendor/**' 'md5\(|sha1\(|FILTER_SANITIZE_STRING' app core || true
rg -n --glob '*.php' --glob '!vendor/**' 'Gem::get\([^\n]*(delete|cancel|reset|toggle|archive)' app/routes.php || true
rg -n --glob '*.php' --glob '!vendor/**' 'Http::url\(|file_get_contents\([^\n]*\$' app core || true
```

- [ ] **Step 2: Run dependency and source audits**

Run: `composer audit`

Run: `sh scripts/audit-source.sh`

Run: `git ls-files | rg '(^|/)(config\.php|\.env|.*\.key|.*\.pem)$'`

- [ ] **Step 3: Write findings with severity, evidence, exploitability, fix, and residual risk**

The report must include the supplied null deprecations, committed runtime-data risk, abandoned dependencies, deterministic nonces, legacy password hashing, destructive GET routes, raw unserialization, webhook method dispatch, remote HTTP timeout behavior, wildcard report lookup, and missing test coverage.

- [ ] **Step 4: Commit**

```bash
git add docs/audits/2026-07-16-security-performance.md scripts/audit-source.sh
git commit -m "docs: add security and performance audit"
```

### Task 2: Harden sessions, cookies, and response headers

**Files:**
- Modify: `core/Gem.class.php`
- Modify: `core/Response.class.php`
- Create: `tests/Security/SessionConfigurationTest.php`
- Create: `tests/Security/ResponseHeadersTest.php`

- [ ] **Step 1: Write failing header and cookie tests**

```php
public function testSecurityHeadersArePresent(): void
{
    $response = \Core\Response::factory('ok');
    self::assertSame('nosniff', $response->headers()['X-Content-Type-Options']);
    self::assertSame('DENY', $response->headers()['X-Frame-Options']);
    self::assertSame('strict-origin-when-cross-origin', $response->headers()['Referrer-Policy']);
}
```

- [ ] **Step 2: Configure session cookies before `session_start()`**

Set `httponly`, `samesite=Lax`, and `secure` when HTTPS is detected. Enable strict session mode and regenerate IDs on authentication transitions.

- [ ] **Step 3: Add compatible default security headers**

Add `X-Content-Type-Options`, `Referrer-Policy`, and frame protection without introducing a strict CSP that would break current inline theme scripts.

- [ ] **Step 4: Run security tests and commit**

Run: `composer test -- --testsuite security`

```bash
git add core/Gem.class.php core/Response.class.php tests/Security
git commit -m "fix: harden sessions and response headers"
```

### Task 3: Replace deterministic action nonces

**Files:**
- Modify: `core/Helper.class.php`
- Create: `tests/Security/NonceTest.php`

- [ ] **Step 1: Write failing nonce tests**

```php
public function testNonceIsBoundToSecretAndAction(): void
{
    $first = \Core\Helper::nonce('billing.cancel');
    $second = \Core\Helper::nonce('billing.cancel');
    self::assertSame($first, $second);
    self::assertNotSame($first, \Core\Helper::nonce('account.delete'));
    self::assertTrue(\Core\Helper::validateNonce($first, 'billing.cancel'));
}
```

- [ ] **Step 2: Replace MD5 construction with HMAC-SHA256**

```php
public static function nonce(string $action): string
{
    $session = session_id() ?: 'no-session';
    return substr(hash_hmac('sha256', $session.'|'.$action, AuthToken), 0, 20);
}
```

Use `hash_equals()` during validation and preserve a temporary legacy-validation path only where deployment compatibility requires it.

- [ ] **Step 3: Run tests and commit**

Run: `composer test -- --filter NonceTest`

```bash
git add core/Helper.class.php tests/Security/NonceTest.php
git commit -m "fix: authenticate action nonces with HMAC"
```

### Task 4: Guard unsafe deserialization and remote requests

**Files:**
- Modify: `app/controllers/admin/DashboardController.php`
- Modify: `core/Http.class.php`
- Create: `tests/Security/SerializationTest.php`
- Create: `tests/Core/HttpTest.php`

- [ ] **Step 1: Write failing tests**

```php
public function testSerializedImportCannotInstantiateClasses(): void
{
    $payload = 'O:8:"stdClass":0:{}';
    self::assertFalse(\Helpers\SafeSerialization::decode($payload));
}
```

- [ ] **Step 2: Decode imports with class creation disabled**

Use `unserialize($content, ['allowed_classes' => false])`, reject objects, cap input size, and validate the expected array shape.

- [ ] **Step 3: Add HTTP defaults**

Set TLS verification, a 5-second connection timeout, a 15-second total timeout, a bounded redirect count, and response-size limits where the transport supports them.

- [ ] **Step 4: Run tests and commit**

Run: `composer test -- --filter 'SerializationTest|HttpTest'`

```bash
git add app/controllers/admin/DashboardController.php core/Http.class.php app/helpers/SafeSerialization.php tests
git commit -m "fix: harden deserialization and outbound HTTP"
```

### Task 5: Apply verified request-path performance fixes

**Files:**
- Modify: `app/controllers/LinkController.php`
- Modify: `app/traits/Links.php`
- Modify: `app/controllers/SubscriptionController.php`
- Update: `docs/audits/2026-07-16-security-performance.md`
- Create: `tests/Performance/RequestPathTest.php`

- [ ] **Step 1: Add query-count or call-count assertions around redirect and checkout paths**

```php
public function testMissingAcceptLanguageDoesNotTriggerRepeatedLookup(): void
{
    $request = new \Core\Request();
    self::assertSame('', $request->serverString('http_accept_language'));
}
```

- [ ] **Step 2: Cache repeated decoded values and avoid duplicate provider enumeration**

Decode URL targeting JSON once per request, reuse provider configuration, and avoid repeated payment trial queries in pricing loops.

- [ ] **Step 3: Document index recommendations that cannot be applied without production query plans**

Record table, columns, query evidence, expected benefit, and rollout risk. Do not add speculative production indexes.

- [ ] **Step 4: Run tests, lint, and commit**

Run: `composer test && sh scripts/lint-php.sh`

```bash
git add app docs/audits tests/Performance
git commit -m "perf: reduce repeated request-path work"
```
