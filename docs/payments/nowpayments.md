# NOWPayments Operations Guide

## Supported modes

The gateway supports three administrator-controlled modes:

1. **Prepaid term**, enabled by default. One final payment purchases one monthly, yearly, or lifetime term.
2. **Email renewal**. NOWPayments creates recurring charges and emails the subscriber a payment link for each period.
3. **Custodial automatic renewal**. NOWPayments charges a funded sub-partner balance. Custody remains locked until all readiness checks pass.

Only provider status `finished` for standard payments or `PAID` for recurring charges grants entitlement. Waiting, confirming, partial, expired, failed, canceled, and custody deposit statuses do not grant plan access.

## Initial setup

1. Run the application updater once after deployment. It creates or upgrades the five prefixed NOWPayments ledger tables and the disabled settings record.
2. Open **Admin > Settings > Payment Gateway > NOWPayments**.
3. Keep the environment on **Sandbox** while validating the integration.
4. Enter the API key and IPN secret. Secret fields are encrypted at rest and are blank when the form reloads. A configured indicator confirms that the stored value remains available.
5. Set the price currency used by local plans, usually `USD`.
6. Set an optional default crypto asset, such as `BTC`. The asset must be enabled in the merchant account.
7. Save settings, configure the displayed HTTPS IPN callback in NOWPayments, then enable the gateway.

Production activation should happen only after a complete sandbox payment reaches the final state and the local account is activated exactly once.

## Email renewal setup

Email renewal requires the NOWPayments dashboard email and password because remote recurring plans use a short-lived dashboard JWT. Save those credentials before enabling the email mode.

Remote plans are synchronized lazily for each local plan, term, amount, currency, mode, and HTTPS IPN callback. Unchanged mappings are reused. Changed amounts, intervals, or callback URLs update the mapped remote plan.

Lifetime plans cannot use recurring modes. They always use prepaid checkout.

## Custodial automatic renewal setup

Custody requires all of the following:

- API key
- IPN secret
- dashboard email
- encrypted dashboard password
- HTTPS IPN callback
- settlement currency
- explicit custody enablement

Save the credentials first. Reload the page and confirm that readiness is green, then enable custodial automatic renewal.

Each local user maps to one deterministic, non-email sub-partner. Funding creates a separate `custodial_deposit` ledger transaction. Its expected provider amount and currency are the estimated crypto deposit values; the original fiat funding target remains in ledger metadata for audit. A successful deposit only funds the sub-partner balance. It never activates a plan. The separate recurring charge must reach `PAID` before entitlement changes.

## Reconciliation cron

Run reconciliation at least every five minutes. The route is:

```text
/crons/nowpayments/{token}
```

Generate `{token}` inside the deployment from:

```php
md5('nowpayments'.AuthToken)
```

Do not publish or commit the generated token. The job processes a bounded batch, discovers new recurring charges by remote plan, polls due payment records, and uses exponential backoff after provider or network failures.

IPN remains the primary update path. Reconciliation recovers delayed or missed callbacks and does not replace signature verification for incoming events.

## Idempotency and records

The existing `payment`, `subscription`, `plans`, and `user` records remain the business source of truth. Dedicated tables preserve provider state:

- `nowpayments_transactions`: payment attempts, one record per recurring billing cycle, custody deposits, retry state, and entitlement guard
- `nowpayments_events`: verified payload hashes and processing history
- `nowpayments_plans`: local to remote recurring plan mappings
- `nowpayments_customers`: local user to custody sub-partner mappings

Unique order IDs, idempotency keys, provider payment IDs, provider cycle keys, and payload hashes prevent duplicate creation and replay. Provider subscription IDs are indexed parent identities and may appear on multiple cycle transactions. A recurring cycle uses the provider payment ID when available, otherwise a stable subscription and provider-period identity such as `expire_date`.

Recurring reconciliation accepts provider responses that omit amount and currency only after matching the remote plan mapping and subscriber identity to the immutable local ledger context. Each new provider period creates a new transaction and local payment, so a later paid renewal extends entitlement exactly once. During the first updater run after this schema change, an older unkeyed recurring row is assigned the currently observed provider cycle without replaying an already-applied entitlement.

## Failure handling

- Invalid or missing IPN signatures return `401` and cannot mutate billing.
- Unknown orders are recorded as rejected verified events and return `202`.
- Amount, currency, order, or provider ID mismatches return `422`.
- Duplicate verified events return `200` without repeating entitlement or affiliate credit.
- Remote request failures mark the local attempt failed without marking an account paid.
- A canceled recurring subscription preserves access already paid through its existing expiration.

Application logs contain only normalized error classes and operational counts. API keys, IPN secrets, dashboard passwords, JWTs, authorization headers, and full sensitive payloads must never be logged.

## Go-live checklist

- Composer validation and audit pass with no abandoned package warning.
- PHPUnit and first-party PHP lint pass on PHP 8.3 and PHP 8.5.
- The IPN callback uses a valid public HTTPS certificate.
- A sandbox prepaid payment activates once.
- A duplicate sandbox IPN does not extend access twice.
- A partial or expired sandbox payment does not activate access.
- Email renewal creates a remote plan and charge record.
- Custody deposit funds balance without activating access.
- A paid custody recurring charge activates access once.
- Reconciliation recovers a deliberately delayed callback.
- Production API and IPN secrets are entered only after sandbox verification.
