# Project ExecPlan

## Purpose

This living ExecPlan coordinates the PHP modernization, security and performance audit, and NOWPayments implementation. The detailed task sequences live in the linked implementation plans.

## Workstreams

1. [PHP 8.3 and 8.5 Compatibility](../docs/superpowers/plans/2026-07-16-php-compatibility.md)
2. [Security and Performance Hardening](../docs/superpowers/plans/2026-07-16-security-performance.md)
3. [NOWPayments Gateway](../docs/superpowers/plans/2026-07-16-nowpayments-gateway.md)

## Progress

- [x] Inventory the application and initialize CodeGraph.
- [x] Connect the local project to the GitHub repository.
- [x] Exclude production uploads, logs, caches, secrets, and generated analysis data from Git.
- [x] Approve the modular gateway and dedicated ledger architecture.
- [x] Write and self-review the design specification.
- [x] Create and push the clean `main` baseline.
- [x] Create `dev` from `main`.
- [x] Execute PHP compatibility plan.
- [ ] Execute security and performance plan.
- [ ] Execute NOWPayments plan.
- [ ] Run final verification and review.
- [ ] Push `dev`, merge into `main`, and push `main`.

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

## Recovery

Each workstream is committed separately on `dev`. If a workstream fails verification, repair it on `dev` without rewriting `main`. The final merge uses `--no-ff` so the workstream history remains visible.
