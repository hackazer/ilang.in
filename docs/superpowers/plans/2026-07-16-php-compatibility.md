# PHP 8.3 and 8.5 Compatibility Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make PHP 8.3 the declared minimum and run first-party code cleanly under PHP 8.5 with regression coverage for known deprecations.

**Architecture:** Add a small PHPUnit harness around isolated framework behavior, modernize dependency constraints, and make surgical compatibility changes. Preserve dynamic framework contracts where strict typing would break existing controllers or plugins.

**Tech Stack:** PHP 8.3 and 8.5, Composer 2, PHPUnit 11, GitHub Actions

---

### Task 1: Establish the compatibility test harness

**Files:**
- Modify: `composer.json`
- Create: `phpunit.xml.dist`
- Create: `tests/bootstrap.php`
- Create: `tests/Compatibility/RuntimeTest.php`

- [ ] **Step 1: Write the failing runtime requirement test**

```php
<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class RuntimeTest extends TestCase
{
    public function testSupportedRuntimeIsPhp83OrNewer(): void
    {
        self::assertGreaterThanOrEqual(80300, PHP_VERSION_ID);
    }
}
```

- [ ] **Step 2: Configure Composer and PHPUnit**

Add `php: ^8.3`, `phpunit/phpunit: ^11.5`, PSR-4 test autoloading, and a `test` script to `composer.json`. Configure `phpunit.xml.dist` to bootstrap `tests/bootstrap.php` and fail on warnings, notices, and deprecations.

- [ ] **Step 3: Install dependencies and run the test**

Run: `composer update --with-all-dependencies`

Run: `composer test -- --filter RuntimeTest`

Expected: one passing test and no deprecation output from first-party code.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock phpunit.xml.dist tests
git commit -m "test: establish PHP 8.3 compatibility harness"
```

### Task 2: Fix nullable request input regressions

**Files:**
- Modify: `core/Request.class.php`
- Modify: `app/controllers/LinkController.php`
- Modify: `app/models/Settings.php`
- Create: `tests/Core/RequestTest.php`

- [ ] **Step 1: Write failing tests for absent string server values**

```php
public function testServerStringReturnsDefaultForMissingHeader(): void
{
    $request = new \Core\Request();
    self::assertSame('', $request->serverString('HTTP_ACCEPT_LANGUAGE'));
    self::assertSame('en', $request->serverString('HTTP_ACCEPT_LANGUAGE', 'en'));
}
```

- [ ] **Step 2: Verify the tests fail because `serverString` is absent**

Run: `composer test -- --filter RequestTest`

Expected: failure reporting an undefined method.

- [ ] **Step 3: Add the typed accessor and use it at string call sites**

```php
public function serverString(string $name, string $default = ''): string
{
    $value = $this->server($name);
    return is_scalar($value) ? (string) $value : $default;
}
```

Use `serverString('http_accept_language')` before `substr()` and normalize `$url->type` to a string before `preg_match()`.

- [ ] **Step 4: Run the regression tests**

Run: `composer test -- --filter 'RequestTest|RuntimeTest'`

Expected: all selected tests pass without deprecations.

- [ ] **Step 5: Commit**

```bash
git add core/Request.class.php app/controllers/LinkController.php app/models/Settings.php tests/Core/RequestTest.php
git commit -m "fix: normalize nullable request strings"
```

### Task 3: Remove PHP 8.4 and 8.5 first-party deprecations

**Files:**
- Modify: `core/Gem.class.php`
- Modify: `core/Helper.class.php`
- Modify: `core/Request.class.php`
- Modify: any first-party PHP file identified by the lint sweep
- Create: `scripts/lint-php.sh`

- [ ] **Step 1: Add a lint script that treats deprecations as failures**

```sh
#!/bin/sh
set -eu
find app core public storage/themes -type f -name '*.php' -print0 |
  xargs -0 -n1 php -d error_reporting=E_ALL -d display_errors=1 -l
php -d error_reporting=E_ALL -d display_errors=1 -l index.php
php -d error_reporting=E_ALL -d display_errors=1 -l config.sample.php
```

- [ ] **Step 2: Run lint and record every first-party deprecation**

Run: `sh scripts/lint-php.sh`

Expected before fixes: failures for implicit nullable parameters and non-canonical casts.

- [ ] **Step 3: Apply mechanical compatibility fixes**

Use explicit nullable types where an existing typed parameter defaults to null, replace `(double)` with `(float)`, replace removed sanitization filters with explicit string validation, and normalize nullable string-function arguments.

- [ ] **Step 4: Run lint and tests**

Run: `sh scripts/lint-php.sh`

Run: `composer test`

Expected: both commands exit successfully with no first-party warnings or deprecations.

- [ ] **Step 5: Commit**

```bash
git add app core public storage/themes scripts/lint-php.sh tests
git commit -m "fix: support PHP 8.5 without deprecations"
```

### Task 4: Add CI and dependency verification

**Files:**
- Create: `.github/workflows/php.yml`
- Modify: `composer.json`

- [ ] **Step 1: Add the PHP matrix workflow**

```yaml
name: PHP
on:
  push:
  pull_request:
jobs:
  verify:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.3', '8.5']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install --no-interaction --prefer-dist
      - run: composer validate --strict
      - run: sh scripts/lint-php.sh
      - run: composer test
      - run: composer audit
```

- [ ] **Step 2: Run the local equivalents**

Run: `composer validate --strict && sh scripts/lint-php.sh && composer test && composer audit`

Expected: every command exits successfully.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/php.yml composer.json composer.lock
git commit -m "ci: verify PHP 8.3 and 8.5"
```
