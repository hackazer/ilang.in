# Project ExecPlan

## Purpose

This living ExecPlan coordinates the PHP modernization, security and performance audit, and NOWPayments implementation. The detailed task sequences live in the linked implementation plans.

## Workstreams

1. [PHP 8.3 and 8.5 Compatibility](../docs/superpowers/plans/2026-07-16-php-compatibility.md)
2. [Security and Performance Hardening](../docs/superpowers/plans/2026-07-16-security-performance.md)
3. [NOWPayments Gateway](../docs/superpowers/plans/2026-07-16-nowpayments-gateway.md)
4. [Dependency and Frontend Runtime Modernization](../docs/superpowers/plans/2026-07-16-dependency-modernization.md)

## Progress

- [x] Inventory the application and initialize CodeGraph.
- [x] Connect the local project to the GitHub repository.
- [x] Exclude production uploads, logs, caches, secrets, and generated analysis data from Git.
- [x] Approve the modular gateway and dedicated ledger architecture.
- [x] Write and self-review the design specification.
- [x] Create and push the clean `main` baseline.
- [x] Create `dev` from `main`.
- [x] Execute PHP compatibility plan.
- [x] Execute security and performance plan.
- [x] Execute NOWPayments plan.
- [x] Execute dependency and frontend runtime modernization plan.
- [x] Run final verification and review.
- [x] Push `dev`, merge into `main`, and push `main`.

## Decisions

- PHP 8.3 is the minimum supported runtime. PHP 8.5 is the primary verification runtime.
- Existing `payment` and `subscription` records remain the business source of truth.
- NOWPayments receives dedicated transaction, event, plan, and customer mapping tables.
- Prepaid is the default payment mode.
- Email renewal and custodial automatic renewal are optional administrator-controlled modes.
- Custodial automatic renewal remains disabled until readiness validation passes.
- Runtime user data and production secrets are never committed.

## Verification Gate

No branch is merged until all of the following are fresh and successful:

- Composer validation and dependency audit
- first-party PHP lint with `E_ALL`
- complete automated test suite
- targeted compatibility regression tests
- CodeGraph synchronization
- secret scan of tracked files
- review of `git diff main...dev`

## Current evidence

- Browser runtime is pinned to Bootstrap 5.3.8, jQuery 4.0.0, Font Awesome 7.3.1, Jodit 4.13.5, Air Datepicker 3.6.0, Coloris 0.25.0, and IMask 7.6.1.
- PHP 8.3 passes PHPUnit 12.5.31 and PHP 8.5 passes PHPUnit 13.2.4. Both runs pass 550 tests and 8,072 assertions.
- `sh scripts/verify-release.sh` passes on PHP 8.5.7, and the equivalent PHP 8.3 lint, Composer, audit, platform, and full-test checks pass.
- The PHP 8.3 minimum intentionally keeps the supported PHPUnit 12.5 line. PHPUnit 13 requires PHP 8.4+, so PHP 8.5 CI runs the newer test runner.
- CodeGraph is indexed and current at 712 files, 7,178 nodes, and 52,958 edges.
- `npm outdated --json` returns no outdated root browser packages. Composer reports only PHPUnit 13.2.4 as newer, which is incompatible with the PHP 8.3 floor.
- The repository-wide PHP 8.5 deprecation guard removes `curl_close()` from tracked theme and plugin PHP sources and verifies the lint script scans all tracked PHP files.

## Recovery

Each workstream is committed separately on `dev`. If a workstream fails verification, repair it on `dev` without rewriting `main`. The final merge uses `--no-ff` so the workstream history remains visible.
