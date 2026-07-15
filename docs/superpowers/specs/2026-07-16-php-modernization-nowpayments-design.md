# PHP Modernization and NOWPayments Design

## Objective

Modernize the application so PHP 8.3 is the declared minimum and PHP 8.5 is a verified target, fix the supplied runtime deprecations, audit high-risk security and performance behavior, and add a production-grade NOWPayments gateway to the existing subscription checkout.

The gateway supports three administrator-selectable modes:

1. Prepaid term, enabled by default. A completed payment activates one monthly, yearly, or lifetime term.
2. Email-link renewal. NOWPayments sends renewal payment links on the configured interval.
3. Custodial automatic renewal. NOWPayments charges a funded customer sub-partner balance. This mode remains unavailable until all custody credentials and settings validate successfully.

## Constraints and Existing Architecture

The application is a legacy GemPixel PHP application with custom routing, request handling, ORM, settings, themes, and payment abstractions. Existing providers are registered in `app/traits/Payments.php`. Checkout is coordinated by `app/controllers/SubscriptionController.php`, provider callbacks enter through `app/controllers/WebhookController.php`, and billing state is stored in the existing `payment`, `subscription`, `plans`, and `users` records.

Existing billing records remain the business source of truth. NOWPayments-specific identifiers, event history, idempotency data, customer mappings, retry state, and remote plan mappings require dedicated tables because they have different lifecycles and uniqueness rules.

The repository initially contained runtime uploads, logs, caches, generated CodeGraph data, and a GeoLite database. Those artifacts are not source code and must remain outside Git.

## Selected Architecture

### Provider adapter

`Helpers\Payments\NowPayments` implements the same provider actions used by Stripe and PayPal:

- `settings`
- `checkout`
- `payment`
- `subscription`
- `webhook`
- `createplan`
- `updateplan`
- `syncplan`
- `cancel`

The provider is registered directly in `Traits\Payments`. It is not implemented as a runtime plugin because NOWPayments participates in core checkout, plan synchronization, webhook routing, account billing, and reconciliation.

### API boundary

The provider delegates network work to small classes under `app/helpers/payments/nowpayments/`:

- `Client.php`: authenticated HTTP requests, timeouts, JSON validation, safe error normalization, and sandbox or production base URL selection.
- `Credentials.php`: validated settings access and encrypted custody credential handling.
- `Signature.php`: canonical IPN payload sorting and HMAC-SHA512 verification using constant-time comparison.
- `Status.php`: provider status normalization and allowed local state transitions.
- `Order.php`: deterministic order identifiers and idempotency keys.
- `Reconciler.php`: safe status polling for pending or ambiguous payments.
- `PlanManager.php`: remote plan creation, update, synchronization, and mapping.
- `CustomerManager.php`: sub-partner provisioning and custody balance mapping.

No API key, IPN secret, dashboard password, JWT, or full signed payload is written to application logs.

### Dedicated ledger

The migration layer creates four tables using the configured database prefix:

#### `nowpayments_transactions`

Stores one row per local payment attempt:

- local user, plan, subscription, and payment IDs
- unique local order ID and idempotency key
- provider payment or invoice ID
- mode: `prepaid`, `email`, or `custodial`
- term: `monthly`, `yearly`, or `lifetime`
- price and pay currencies, expected amount, received amount, and outcome amount
- normalized local status and raw provider status
- pay address, memo or tag, expiration, last checked time, retry count, and next retry time
- sanitized provider metadata JSON
- created and updated timestamps

Unique indexes prevent duplicate provider IDs, order IDs, and idempotency keys.

#### `nowpayments_events`

Stores verified callback history:

- unique payload hash
- provider payment ID and transaction ID
- signature verification result
- event status
- processing result and failure reason
- sanitized payload JSON
- received and processed timestamps

The unique payload hash makes webhook replay processing idempotent.

#### `nowpayments_plans`

Maps each local plan and term to a remote recurring plan:

- local plan ID and term
- mode: `email` or `custodial`
- remote plan ID
- amount, currency, interval days, and synchronization hash
- active state, last synchronized time, and sanitized metadata

#### `nowpayments_customers`

Maps a local user to a NOWPayments custody sub-partner:

- local user ID
- unique provider sub-partner ID and generated non-email name
- provisioning status
- last balance synchronization time
- sanitized metadata and timestamps

### Configuration

Administrator settings include:

- provider enabled state
- environment: sandbox or production
- default mode, initially `prepaid`
- enabled modes
- API key
- IPN secret
- accepted pay currencies or dynamic checked-currency mode
- settlement currency
- partial-payment policy
- payment expiration and reconciliation interval
- fee-paid-by-user policy
- email renewal settings
- custody dashboard email and encrypted password
- custody readiness test and automatic-renewal enablement

Custodial automatic renewal cannot be enabled unless the API key, dashboard authentication, sub-partner API access, remote plan synchronization, callback URL, and IPN secret all pass validation. The admin screen displays readiness failures without exposing secret values.

## Payment Flows

### Prepaid term

1. The authenticated user chooses a plan, term, NOWPayments, and pay currency.
2. Server-side code recalculates price, coupon, and tax using current database data. Browser-supplied amounts are ignored.
3. A pending local subscription and dedicated ledger transaction are created before the remote request.
4. The server creates a NOWPayments payment with a deterministic order ID and the provider-specific callback URL.
5. The checkout status screen shows amount, currency, address, memo or tag, QR representation, expiration, and status.
6. Verified IPN events or reconciliation polling advance the transaction state.
7. Exactly one successful transition creates or updates the local payment record, activates the local subscription, updates user entitlement, and dispatches `payment.success`.

### Email-link renewal

1. The relevant local plan and term are synchronized to a NOWPayments recurring plan.
2. The user confirms enrollment and the application creates an email subscription using the remote plan ID and verified account email.
3. The local subscription records the provider subscription ID and remains active only for confirmed paid periods.
4. Verified renewal events extend entitlement from the later of the current expiration or the provider payment time.
5. Cancellation disables future renewal links and preserves already-paid access.

### Custodial automatic renewal

1. The administrator completes custody readiness validation.
2. A local user is provisioned once as a NOWPayments sub-partner using a deterministic non-email identifier.
3. The user funds the custody balance through a NOWPayments deposit flow.
4. Enrollment creates a recurring charge using the remote plan ID and sub-partner ID.
5. Verified paid events extend entitlement. Waiting, partial, expired, or failed events never grant access.
6. Cancellation removes the remote recurring payment and marks the local subscription canceled without shortening an already-paid period.

## Status and Idempotency Rules

Provider statuses are normalized to `pending`, `confirming`, `paid`, `partial`, `expired`, `failed`, `refunded`, and `canceled`.

Only `finished` or the documented equivalent maps to locally paid. `confirmed` is not treated as final unless current provider documentation explicitly guarantees settlement for the selected endpoint.

State transitions are monotonic. Terminal states cannot move back to pending. Duplicate IPNs return a successful acknowledgment after verifying that the original event was already processed. Unknown transactions are recorded as rejected events and do not mutate billing state.

Entitlement activation occurs inside a database transaction with a row lock or equivalent uniqueness guard. Repeated callbacks, reconciliation runs, and browser refreshes cannot grant duplicate time or duplicate affiliate credit.

## Security Design

- Verify IPN signatures over recursively sorted JSON using HMAC-SHA512 and `hash_equals`.
- Reject missing signatures, invalid JSON, oversized payloads, unexpected content types, and unsupported statuses.
- Re-fetch provider payment status for high-risk or ambiguous events before granting entitlement.
- Store encrypted custody credentials using the application's encryption key, never plaintext settings JSON.
- Apply strict connection and total timeouts, TLS verification, bounded retries with jitter, and no retry for invalid requests.
- Enforce authentication and CSRF protection for checkout creation, enrollment, cancellation, and admin settings.
- Use POST for state changes. Existing destructive GET routes found by the audit are tracked for targeted remediation.
- Validate currency codes against the provider's checked-currency endpoint and enforce server-side amount limits.
- Redact secrets, authorization headers, wallet addresses where appropriate, and sensitive provider payload fields from logs.
- Add security headers and cookie hardening where compatible with the current deployment.

## PHP 8.3 and 8.5 Modernization

The compatibility phase will:

- declare PHP `^8.3` in Composer and refresh dependencies to releases supporting PHP 8.3 and 8.5
- replace abandoned PayPal SDK usage where a compatible release is unavailable, or isolate it behind a disabled legacy provider until migrated
- fix implicit nullable signatures, null passed to string functions, deprecated filters, non-canonical casts, dynamic properties where encountered, and PHP 8.5 warnings
- add return and parameter types only where they do not break the framework's dynamic contracts
- lint all first-party PHP files under PHP 8.5 with `E_ALL`
- add a PHP 8.3 and 8.5 CI matrix

The supplied `LinkController.php` failures are fixed by normalizing absent request headers and nullable URL type values before calling `substr()` or `preg_match()`.

## Performance Audit

The audit prioritizes request-path behavior with measurable impact:

- repeated database queries in redirect and checkout paths
- unbounded table scans and wildcard searches
- synchronous remote safety, geolocation, and payment calls without strict timeouts
- repeated configuration and theme reads
- cache invalidation and runtime file access
- oversized frontend bundles and duplicate minified or unminified assets
- database indexes required by webhook and reconciliation lookups

Changes must preserve behavior and include a benchmark or query-count assertion where practical. Broad rewrites of the custom framework are excluded unless a verified defect makes a focused change impossible.

## UI and Accessibility

The existing Bootstrap and theme architecture remains intact. The payment UI receives a focused upgrade rather than a site-wide redesign:

- clear provider and payment-mode selection
- progressive disclosure for prepaid, email renewal, and custody requirements
- accessible labels, keyboard operation, focus states, status announcements, and error summaries
- responsive payment instructions with copy controls and QR presentation
- status timeline for waiting, confirming, paid, partial, expired, failed, refunded, and canceled states
- no secret values rendered back into admin HTML after save
- reduced-motion support and no motion that interferes with payment completion

The requested frontend design skills guide hierarchy, spacing, interaction, responsive behavior, and accessibility. They do not justify replacing the project's stack with React, Tailwind, GSAP, or shadcn components.

## Testing Strategy

The project receives a lightweight first-party test harness compatible with the custom framework.

Automated coverage includes:

- signature verification with valid, invalid, reordered, nested, and malformed payloads
- status transition rules and terminal-state protection
- deterministic order and idempotency generation
- client authentication, timeout, retry, redaction, and error normalization using a fake transport
- price, coupon, tax, and term calculation
- prepaid activation exactly once
- email subscription plan mapping and renewal extension
- custody customer provisioning and automatic renewal enrollment
- duplicate, replayed, unknown, partial, expired, failed, and refunded IPNs
- reconciliation of missed callbacks
- PHP 8.5 deprecation regression cases from the supplied logs
- route method and middleware assertions for payment state changes

Live API verification is optional and requires explicit sandbox credentials. The implementation is complete without secrets, but production activation remains disabled until the administrator passes the readiness checks.

## Delivery Sequence

1. Create a safe baseline commit on `main` without runtime data or secrets and push it.
2. Create `dev` from `main`.
3. Add compatibility tests and complete PHP 8.3 and 8.5 modernization.
4. Complete the security and performance audit, then apply verified high-priority fixes.
5. Add the NOWPayments schema, client, provider, flows, UI, IPN handling, and reconciliation.
6. Run complete verification and review the final diff.
7. Push `dev`.
8. Merge `dev` into `main` with a non-fast-forward merge and push `main`.

## Acceptance Criteria

- Composer declares PHP 8.3 as the minimum and resolves to dependencies compatible with PHP 8.5.
- First-party PHP lint completes under PHP 8.5 with no deprecation warnings.
- The supplied null-to-string deprecations have regression coverage and are fixed.
- Critical and high-confidence security findings in modified or payment-critical code are fixed or documented with explicit residual risk.
- NOWPayments appears in existing admin settings and checkout only when enabled.
- Prepaid is the default mode and works for monthly, yearly, and lifetime terms.
- Email renewal and custodial automatic renewal can be independently enabled and disabled.
- Custodial automatic renewal cannot activate without passing readiness checks.
- Valid final payments activate entitlement exactly once.
- Invalid signatures, duplicate callbacks, partial payments, failed payments, expired payments, and unknown orders never grant entitlement.
- Reconciliation recovers a missed valid callback without duplicate activation.
- No credentials or runtime user data are committed to Git or written to logs.
- CI runs tests and lint on PHP 8.3 and PHP 8.5.
- `dev` and `main` are pushed, and `main` contains the verified merge.
