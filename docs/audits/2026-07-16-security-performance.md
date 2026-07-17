# Security, Performance, and PHP Compatibility Audit

**Audit date:** 2026-07-16  
**Repository:** `hackazer/ilang.in`  
**Reviewed branch:** `dev` worktree  
**Release baseline:** `main` at `f9d7bbf`  
**Scope:** first-party PHP under `app/` and `core/`, routes, payment integrations, upload and restore paths, Composer metadata, tests, tracked runtime files, and request-path database/cache behavior.

## Executive assessment

The original `main` baseline was not safe for an Internet-facing release. The `dev` branch now closes the release-blocking payment, webhook, SSRF, backup, upload, session, proxy, password, quota, cache, query, and PHP compatibility findings with focused regression coverage.

All identified destructive routes now use POST and CSRF while retaining their existing session-bound HMAC nonce, authorization, and ownership checks. The remaining GET routes reported by the broad source heuristic are read-only archive or filter views, password reset and activation landing routes, and the bookmark endpoint.

The dependency locks contain no known advisories or abandoned Composer packages. Production dependencies are being moved to the latest stable releases compatible with PHP 8.3, and browser compatibility holds have been rejected as release policy. The release migration replaces Bootstrap 4, Popper 1, discontinued Font Awesome Iconpicker, the Moment date-range stack, Spectrum, the legacy font selector, jQuery Mask, and stale embedded AdminKit runtimes with maintained equivalents while preserving their workflows. PHPUnit uses the latest actively supported runner for each tested runtime: PHPUnit 12.5 on PHP 8.3 and PHPUnit 13 on PHP 8.5.

## Severity and status definitions

| Rating | Meaning |
| --- | --- |
| Critical | A practical path can alter billing entitlement, expose sensitive internal services, or cause equivalent business-critical compromise. |
| High | Exploitation can perform unauthorized state changes, execute dangerous parsing, or materially weaken a trust boundary. |
| Medium | Exploitation or failure needs more conditions, but can cause abuse, data mix-ups, degraded controls, or sustained operational impact. |
| Low | Defense-in-depth, maintainability, or repository-hygiene risk with limited direct exploitability. |

| Status | Meaning |
| --- | --- |
| Open | Confirmed in the reviewed worktree and no verified merged remediation exists. |
| In progress | Development work exists or is being prepared, but it is not merged and therefore is not accepted as a fix. |
| Verify after merge | The development code appears to address the finding, but the release branch and production behavior remain unverified. |
| Resolved on dev | The fix is committed on `dev` and has focused automated regression coverage. |

## Prioritized findings

| ID | Severity | Area | Status |
| --- | --- | --- | --- |
| SEC-01 | Critical | PayPal Basic IPN can grant entitlement without complete merchant and payment validation | Resolved on dev |
| SEC-02 | Critical | Stripe webhook authentication fails open and events are not idempotent | Resolved on dev |
| SEC-03 | High | User-configurable outbound webhooks permit blind SSRF | Resolved on dev |
| SEC-04 | High | State-changing admin actions use GET and a predictable unkeyed nonce | Resolved on dev |
| SEC-05 | High | Backup restore uses unrestricted PHP deserialization | Resolved on dev |
| SEC-06 | Medium | Session and persistent cookie defaults are incomplete | Resolved on dev |
| SEC-07 | Medium | Client IP headers are trusted without a proxy allowlist | Resolved on dev |
| SEC-08 | Medium | Upload MIME validation trusts the client-provided content type | Resolved on dev |
| SEC-09 | Medium | Report and banned-link matching uses ambiguous leading-wildcard lookup | Resolved on dev |
| SEC-10 | Medium | Payment webhook routes accept GET as well as POST | Resolved on dev |
| SEC-11 | Medium | Legacy link passwords retain unsalted MD5 compatibility | Resolved on dev |
| SEC-12 | Low | Runtime and user-generated files can be accidentally committed | Resolved on dev |
| SEC-13 | Critical | Bio rich-text blocks permit stored active-content injection | Resolved on dev |
| SEC-14 | Critical | Team and splash mutations are not consistently tenant-scoped | Resolved on dev |
| SEC-15 | High | RSS blocks bypass outbound URL controls | Resolved on dev |
| SEC-16 | High | Language ZIP packages are extracted without a package allowlist | Resolved on dev |
| PERF-01 | High | Monthly click-limit cache is stale for up to 24 hours | Resolved on dev |
| PERF-02 | Medium | User statistics read tenant-specific keys but write global admin keys | Resolved on dev |
| PERF-03 | Medium | Dashboard, user-list, profile, and pricing paths contain N+1 queries | Resolved for audited paths |
| PERF-04 | Medium | Public shortening throttle is reset by discarding the session cookie | Resolved on dev |
| PERF-05 | High | Click counters lose updates and monthly predicates bypass indexes | Resolved on dev |
| PERF-06 | Medium | API geolocation blocks redirects and repeats work per click | Resolved on dev |
| COMP-01 | High | PHP 8.5 nullable string-function deprecations are present on `main` | Resolved on dev |
| COMP-02 | High | PHP 8.5 deprecated cURL close calls are present on `main` | Resolved on dev |
| COMP-03 | High | Composer retains an abandoned PayPal SDK | Resolved on dev |
| COMP-04 | Medium | PHP runtime policy and compatibility coverage are not yet release-proven | Resolved on dev |

## Security findings

### SEC-01: PayPal Basic IPN can grant entitlement without complete merchant and payment validation

**Severity:** Critical  
**Evidence:** `app/helpers/payments/Paypal.php:143-241`; `app/helpers/payments/IpnListener.php:88-123`.

The listener posts the payload back to PayPal and checks its response, but TLS peer verification is explicitly disabled at `IpnListener.php:100`. After a positive response, the handler trusts attacker-supplied `custom` JSON to select the user, plan, term, and renewal behavior. It does not require `payment_status=Completed`, verify `receiver_email` or merchant identity, compare `mc_currency`, or compare `mc_gross` with the server-side plan price before changing the user's plan and expiration. Existing transactions are looked up by `txn_id`, but a prior row is updated and returned without validating the immutable transaction context.

**Exploit and impact:** A forged or misdirected callback, a transaction paid to another merchant, a wrong-currency payment, a partial amount, or a replay with manipulated `custom` data can produce unauthorized paid entitlement. Disabling TLS peer verification also exposes the verification exchange to an active network attacker.

**Recommended remediation:** Fail closed unless the request is POST, TLS verification succeeds, the verification response is exactly valid, status is an entitlement-granting terminal status, merchant identity matches configuration, currency and gross amount match a server-derived invoice, and the transaction ID is unique. Store the expected user, plan, amount, currency, and provider transaction before redirecting to PayPal. Apply payment and entitlement changes in one transaction with a unique provider-event key.

**Current status:** Resolved on `dev`. PayPal now requires verified TLS, exact provider validation, completed status, merchant identity, currency, amount, and idempotent transaction context before entitlement changes. Covered by `PaypalIpnTest` and payment integration regressions.

### SEC-02: Stripe webhook authentication fails open and events are not idempotent

**Severity:** Critical  
**Evidence:** `app/helpers/payments/Stripe.php:453-539`; `app/routes.php:515-518`.

Signature verification is conditional on `config('stripe')->sig` being non-empty. When the signing secret is absent, arbitrary JSON proceeds directly to event processing. The handler creates payment rows and extends subscription and user expiration without first claiming a unique Stripe event or charge identifier. It generates a random local `tid` for each delivery instead of using the provider event as the idempotency boundary.

**Exploit and impact:** A deployment with an omitted signing secret accepts unauthenticated entitlement-changing payloads. Even with a signing secret, Stripe retries or deliberate replay can duplicate payment rows, increase subscription totals, and repeatedly extend expiry.

**Recommended remediation:** Make the webhook signing secret mandatory whenever Stripe is enabled. Reject missing or malformed signature headers before decoding business data. Persist the Stripe event ID and relevant object ID under unique constraints, process only supported event types, derive the account and amount from trusted local subscription state, and commit event, payment, subscription, and entitlement updates atomically.

**Current status:** Resolved on `dev`. Stripe rejects missing signing configuration, validates signatures before business parsing, and claims provider event identifiers idempotently before entitlement updates. Covered by `StripeWebhookTest`.

### SEC-03: User-configurable outbound webhooks permit blind SSRF

**Severity:** High  
**Evidence:** `app/controllers/ServerController.php:50-63`, `app/controllers/ServerController.php:113-126`, `app/traits/Links.php:430-432`, `app/traits/Links.php:784-786`, `app/controllers/WebhookController.php:77-138`, and `core/Http.class.php:165-253`.

Stored contact, Zapier, and Slack response URLs are passed to the generic HTTP client without a centralized outbound URL policy. The client does not reject loopback, link-local, private, multicast, or cloud metadata addresses, and several call sites do not provide timeouts. DNS resolution is not pinned or rechecked, leaving DNS rebinding possible. The generic client can also disable TLS verification through runtime flags.

**Exploit and impact:** A user who controls a webhook URL can cause the server to send requests to internal services, localhost, private network hosts, or metadata endpoints. Responses are generally not returned to the attacker, so the primary confirmed class is blind SSRF, but timing and side effects may still expose or modify internal services. Missing default timeouts can also tie up PHP workers.

**Recommended remediation:** Add one outbound URL validator and resolver used by every user-controlled callback. Allow only `https`, reject embedded credentials and nonstandard schemes, resolve all addresses, block private and special-use ranges for IPv4 and IPv6, revalidate redirect targets, cap redirects, and enforce connection, total-time, and response-size limits. Prefer explicit allowlists for Slack and other known providers.

**Current status:** Resolved on `dev`. User-controlled callbacks pass through centralized scheme, address-range, DNS, redirect, timeout, and response-size controls. Covered by `OutboundUrlTest`.

### SEC-04: State-changing admin actions use GET and a predictable unkeyed nonce

**Severity:** High  
**Evidence:** representative routes at `app/routes.php:240-249`, `app/routes.php:268-298`, `app/routes.php:318-367`, and `app/routes.php:385-415`; nonce implementation at `core/Helper.class.php:828-846`.

Delete, toggle, archive, approve, ban, activate, payment-marking, affiliate-payment, theme, plugin, language, and maintenance operations are exposed as GET routes. Some include a nonce, while others do not. The nonce is ten characters derived from the current time window and action through unkeyed MD5. It is not bound to the authenticated user, session, object ID, or a server secret, and validation uses ordinary equality.

**Exploit and impact:** Links, prefetchers, browser history, crawlers, cross-site image requests, and malicious pages can trigger administrative mutations. The nonce construction is predictable once the action and current time are known, so it is not an authentication mechanism.

**Recommended remediation:** Move every mutation to POST, PATCH, or DELETE as appropriate. Require the existing session CSRF token and authorization check at the controller boundary. Replace action nonces with an HMAC bound to action, object ID, authenticated principal, session, and expiry, validated using `hash_equals`. Keep GET routes read-only and return `405 Method Not Allowed` for mutation attempts.

**Current status:** Resolved on `dev`. The nonce is a session-bound HMAC validated with `hash_equals`. All 28 identified destructive GET routes and 58 theme call sites now use POST plus CSRF while retaining their existing HMAC, authorization, and ownership checks. Read-only archive and filter routes remain GET by design.

### SEC-05: Backup restore uses unrestricted PHP deserialization

**Severity:** High  
**Evidence:** `app/controllers/admin/DashboardController.php:759-767`, followed by table-driven restore logic in the same method.

An uploaded `.gem` file is read and passed directly to `unserialize()` without `allowed_classes=false`, a byte-size cap, an object rejection step, or strict validation of the expected tables and row shapes.

**Exploit and impact:** Anyone who gains access to the backup restore action, or convinces an administrator to restore a malicious file, can attempt PHP object injection through autoloadable gadget classes. The table-driven restore also creates a high-integrity data corruption risk if unexpected keys or structures are accepted.

**Recommended remediation:** Prefer a versioned JSON backup format authenticated with a deployment-specific signature. For legacy files, call `unserialize($payload, ['allowed_classes' => false])`, reject all objects and references, cap file size, require a strict top-level schema, allowlist table names and columns, validate row counts and scalar types, and restore inside a transaction with a dry-run summary.

**Current status:** Resolved on `dev`. Legacy backups are size-bounded, decoded with class creation disabled, schema-validated, allowlisted, and restored transactionally. The ORM legacy decoder also disables class instantiation. Covered by `BackupRestoreTest` and `OrmLegacySerializationTest`.

### SEC-06: Session and persistent cookie defaults are incomplete

**Severity:** Medium  
**Evidence:** `core/Gem.class.php:98-100`, `core/Request.class.php:611-619`, `core/Auth.class.php:236-240`, and absence of default security headers in `core/Response.class.php:119-155`.

The application starts the session before configuring strict session mode or explicit cookie attributes. The generic cookie writer and logout path hard-code `Secure=false`; they set `HttpOnly=true` but do not set `SameSite`. Response emission does not establish baseline `X-Content-Type-Options`, frame protection, or `Referrer-Policy` headers. Login paths do regenerate session IDs, so session fixation is not asserted as a confirmed defect.

**Exploit and impact:** Persistent authentication cookies can traverse an accidental HTTP request and are more exposed to cross-site request contexts. Missing headers increase clickjacking and MIME-sniffing exposure. Impact depends on deployment-level TLS redirects and reverse-proxy headers, which were not available for this code audit.

**Recommended remediation:** Before `session_start()`, enable strict mode and set `HttpOnly`, `SameSite=Lax`, and `Secure` when the request is known to be HTTPS through a trusted proxy. Apply equivalent options to persistent authentication cookies and deletion. Add compatible default response headers, then introduce CSP only after inventorying inline scripts.

**Current status:** Resolved on `dev`. Session strict mode, secure cookie attributes, trusted HTTPS detection, and baseline response headers are covered by security regressions.

### SEC-07: Client IP headers are trusted without a proxy allowlist

**Severity:** Medium  
**Evidence:** `core/Request.class.php:453-464`.

The request IP method accepts Cloudflare, real-IP, client-IP, and multiple forwarded headers from any direct caller before considering `REMOTE_ADDR`. It neither requires the immediate peer to be a configured proxy nor selects a validated address from a trusted proxy chain.

**Exploit and impact:** Attackers can spoof IP-based rate limits, unique-click logic, audit records, geolocation, and security decisions whenever the origin is directly reachable or a proxy forwards untrusted headers unchanged.

**Recommended remediation:** Default to `REMOTE_ADDR`. Accept forwarded headers only when the immediate peer belongs to an administrator-configured proxy CIDR list. Parse the header chain from the trusted edge, validate each address with `filter_var`, and document the required reverse-proxy configuration.

**Current status:** Resolved on `dev`. Forwarded addresses are accepted only from configured trusted proxies and validated before use. Covered by `TrustedProxyIpTest`.

### SEC-08: Upload MIME validation trusts the client-provided content type

**Severity:** Medium  
**Evidence:** `core/Request.class.php:183-203`; representative consumers at `app/controllers/admin/ThemesController.php:172-177`, `app/controllers/admin/PluginsController.php:133-139`, and `app/controllers/user/VerificationController.php:67-75`.

`mimematch` compares the filename extension with `$_FILES[*]['type']`, which is supplied by the client. It does not inspect file bytes with `finfo`, validate image decoding, inspect archive entries, or enforce a shared upload size ceiling. Theme and plugin ZIP uploads are especially sensitive because their contents can become executable application code after extraction.

**Exploit and impact:** A crafted upload can claim an allowed content type. Exploitability depends on each destination, extraction behavior, web-server execution policy, and administrator access, but the current validation does not provide a trustworthy content boundary.

**Recommended remediation:** Detect MIME from the temporary file with `finfo`, validate image files by decoding them, reject SVG unless sanitized, cap bytes before parsing, and inspect ZIP entries for traversal, symlinks, absolute paths, nested archives, and executable files. Store ordinary user uploads outside executable web roots with generated names.

**Current status:** Resolved on `dev`. Uploads use byte-derived MIME checks, size limits, archive traversal and symlink rejection, expansion limits, and package-specific validation. Covered by request upload and archive validator tests.

### SEC-09: Report and banned-link matching uses ambiguous leading-wildcard lookup

**Severity:** Medium  
**Evidence:** redirect check at `app/controllers/LinkController.php:236-237`; report resolution at `app/controllers/admin/LinksController.php:239-255` and `app/controllers/admin/LinksController.php:291-301`.

The redirect path blocks a URL when `bannedlink LIKE '%<destination>%'`, and report resolution matches domains with `LIKE '%<host>'`. These are substring or suffix matches rather than normalized exact host and URL comparisons. Leading wildcards also prevent normal B-tree index use.

**Exploit and impact:** A short or overlapping banned value can disable unrelated links. Ambiguous domain suffixes can associate an administrator report action with the wrong record. At scale, every redirect may require a scan of the reports table.

**Recommended remediation:** Store canonical scheme, host, port, and normalized destination hash in dedicated columns. Match exact normalized hosts or explicit registrable-domain rules, and match destination hashes exactly. Add indexes only after collecting production query plans and cardinality.

**Current status:** Resolved on `dev`. Redirect blacklist and administrative report resolution use canonical exact comparisons instead of leading-wildcard scans. Covered by `TargetedLinkBlacklistTest`.

### SEC-10: Payment webhook routes accept GET as well as POST

**Severity:** Medium  
**Evidence:** `app/routes.php:515-518`; provider dispatch at `app/controllers/WebhookController.php:38-67`.

Both the legacy `/ipn` route and generic `/webhook[/{provider}]` route accept GET and POST. The dispatcher does not enforce the HTTP method before invoking a provider callback.

**Exploit and impact:** Providers that read request values rather than raw POST bodies can be reached through browser or crawler GET requests. This enlarges the attack surface, complicates signature semantics, and makes accidental invocation possible.

**Recommended remediation:** Register payment callbacks as POST-only. Require each provider adapter to reject non-POST requests before reading input, return a deterministic non-2xx response for invalid methods, and keep browser return URLs separate from server-to-server webhooks.

**Current status:** Resolved on `dev`. PayPal, generic provider, and NOWPayments callback routes are POST-only. Covered by route and webhook security tests.

### SEC-11: Legacy link passwords retain unsalted MD5 compatibility

**Severity:** Medium  
**Evidence:** `app/controllers/LinkController.php:135-146`; modern account password helpers at `core/Helper.class.php:509-522`.

Account passwords use bcrypt with an application secret, but legacy 32-character link passwords are verified by applying unsalted MD5 to the submitted password and comparing with ordinary inequality. There is no per-record salt or automatic rehash after successful verification.

**Exploit and impact:** A database leak allows rapid offline cracking of legacy protected-link passwords, particularly common or short values. Timing leakage from ordinary comparison is secondary to the weakness of MD5 storage.

**Recommended remediation:** Store all new link passwords with `password_hash`. On successful legacy verification, immediately rehash with the current algorithm and overwrite the legacy value. Use `password_verify` and rate-limit failed unlock attempts by trusted client identity plus link ID.

**Current status:** Resolved on `dev`. New link passwords use `password_hash`; successful plaintext or MD5 legacy verification migrates and rehashes stale hashes. Covered by `LinkPasswordTest`.

### SEC-12: Runtime and user-generated files can be accidentally committed

**Severity:** Low  
**Evidence:** `.gitignore`; tracked sentinel and generated-looking files under `public/content/` and `storage/logs/`.

The repository now ignores `config.php`, logs, caches, worktrees, and user-generated content while retaining directory guard files. However, already tracked assets such as `public/content/logo.min.png` and `public/content/favicon.min.ico` remain, and broad future force-add operations could still include runtime data.

**Exploit and impact:** Secrets, logs, backups, customer uploads, or operational data committed to Git remain in history even after later deletion. The current tracked-file scan did not identify `config.php`, `.env`, private keys, PEM files, SQL dumps, or log contents.

**Recommended remediation:** Keep the current ignore rules, add a CI secret and runtime-path scan, prohibit force-adding runtime directories, and store deployment configuration outside the checkout. If historical secrets are discovered, rotate them before rewriting history.

**Current status:** Resolved on `dev`. Runtime paths are ignored and the release gate rejects tracked secret or runtime files. This remains a mandatory release check.

### Follow-up security review findings

The post-implementation review found four additional release blockers that were not visible in the baseline-only pass:

- Bio HTML from the rich-text editor reached rendering without a strict server-side allowlist. A reusable fail-closed sanitizer now removes active elements, event attributes, unsafe schemes, inline styles, malformed structures, and unapproved frames on both write and legacy read paths.
- Team deletion trusted a tenant identifier from the route, and splash edit or update lookups were not owner-scoped. Both flows now derive tenant identity from the authenticated user and require POST plus CSRF.
- RSS profile blocks fetched user-controlled URLs through the PHP stream wrapper. RSS retrieval now uses the shared public-address policy, DNS pinning, verified TLS, disabled redirects, strict timeouts, and a bounded response writer.
- Language packages used unrestricted ZIP extraction. The installer now permits only a static locale translation file, validates its structure, stages privately, publishes atomically, and rejects traversal, links, duplicate entries, nested archives, executable content, and archive bombs.

Each fix has focused regression coverage on PHP 8.3 and PHP 8.5.

## Performance and reliability findings

### PERF-01: Monthly click-limit cache is stale for up to 24 hours

**Severity:** High  
**Evidence:** `app/traits/Links.php:718-735`.

The redirect path reads `monthlyclicks<user-id>` and, on a miss, caches the database count for 24 hours. The code then inserts click statistics without incrementing or invalidating the cached count. It also increments and saves the URL's total click count before checking the cached monthly plan limit.

**Impact:** A user can exceed the purchased monthly click allowance by all traffic received during the cache lifetime. Once the stale count reaches the limit, cache timing can also deny valid service after administrative or billing changes. The pre-limit URL increment makes aggregate counters disagree with retained statistics.

**Recommended remediation:** Enforce quota with an atomic counter keyed by user and calendar month, increment it in the same accepted-click path, and use database locking or an atomic cache primitive. Check quota before mutating URL totals. Reconcile the counter from durable statistics and invalidate it when plans change.

**Current status:** Resolved on `dev`. Quota enforcement uses an atomic monthly counter and updates only accepted clicks. Covered by request-path and concurrency regressions.

### PERF-02: User statistics read tenant-specific keys but write global admin keys

**Severity:** Medium  
**Evidence:** `app/controllers/user/StatsController.php:78-99` and `app/controllers/user/StatsController.php:141-147`; administrator keys at `app/controllers/admin/StatsController.php:56-70` and `app/controllers/admin/StatsController.php:152-158`.

User statistics read `stats.chartlinks<user-id>` and `stats.countrymaps<user-id>` but write results to `chartlinks` and `countrymaps`. Those write keys are the same global keys consumed by the administrator statistics controller.

**Impact:** User requests never warm their intended tenant cache, so the same expensive queries recur. More seriously, a user request can overwrite the administrator's global charts with that user's data for up to one hour, producing cross-scope data contamination and misleading operational dashboards.

**Recommended remediation:** Centralize cache-key construction and include namespace, scope, tenant ID, date range, locale, and schema version. Add tests asserting that user writes use exactly the same tenant key they read and cannot collide with administrator keys.

**Current status:** Resolved on `dev`. User and administrator statistics use distinct, scope-correct cache keys with matching read and write behavior.

### PERF-03: Dashboard, user-list, profile, and pricing paths contain N+1 queries

**Severity:** Medium  
**Evidence:** user dashboard loops at `app/controllers/user/DashboardController.php:43-49` and `app/controllers/user/DashboardController.php:61-76`; admin dashboard at `app/controllers/admin/DashboardController.php:55-63`; admin user lists at `app/controllers/admin/UsersController.php:40-50` and `app/controllers/admin/UsersController.php:70-74`; profile blocks and pixels at `app/helpers/Gate.php:502-504` and `app/helpers/Gate.php:567-573`; repeated trial lookup at `app/controllers/SubscriptionController.php:83-89` and `app/controllers/SubscriptionController.php:140-146`.

Each displayed row can trigger one or more additional queries for bundles, URLs, QR records, profiles, users, plans, block links, pixels, or trial history. Pricing invokes the same payment-history query twice per plan.

**Impact:** Query count grows linearly with rows and blocks. Dashboard and bio-page latency will degrade as content grows, while database connection use increases disproportionately under concurrency.

**Recommended remediation:** Collect foreign IDs, load related records in batched `IN` queries, and map them in memory. Fetch trial eligibility once before iterating plans. For administrator user lists, use grouped counts and one plan lookup map. Add query-count regression tests before and after each change.

**Current status:** Resolved for the audited paths on `dev`. Dashboard, user-list, social referrer, pricing, and related lookups use grouped or batched queries. Additional indexes still require production `EXPLAIN` evidence and are not guessed in this release.

### PERF-04: Public shortening throttle is reset by discarding the session cookie

**Severity:** Medium  
**Evidence:** route attachment at `app/routes.php:54`; limiter implementation at `app/middleware/ShortenThrottle.php:43-71`.

The limiter creates a random key in the current PHP session and counts against `shorten<key>`. A caller receives a new bucket by omitting or replacing the session cookie. When cache is disabled, the limiter returns immediately and provides no protection.

**Impact:** Automated clients can bypass the five-request window without rotating IP addresses. This increases spam, abuse, database growth, and outbound reputation risk.

**Recommended remediation:** Use a composite identity based on authenticated user ID or API key, plus a trusted client-IP prefix and abuse signals for anonymous callers. Store counters in an atomic shared backend, fail with explicit operational policy when that backend is unavailable, and apply separate burst and sustained limits.

**Current status:** Resolved on `dev`. Anonymous shortening uses a durable composite identity rather than a discardable session-only bucket. Covered by `ShortenThrottleTest`.

### PERF-05: Click accounting loses concurrent updates and monthly predicates bypass indexes

**Severity:** High

The original click path incremented ORM objects and saved them back, so concurrent requests could overwrite each other's totals. Monthly quota counts wrapped `date` in `MONTH()` and `YEAR()`, preventing a normal range scan.

**Current status:** Resolved on `dev`. Click and unique-click updates use one atomic SQL counter update inside serialized URL accounting. Monthly quota counts use half-open calendar bounds and the schema provides `stats(urluserid,date)` and `stats(urlid,ip)` composite indexes. Concurrency and calendar-boundary regressions cover the behavior.

### PERF-06: Redirect geolocation is repeated synchronously

**Severity:** Medium

The API geolocation driver performed an uncached network request on every accepted click with the shared transport timeout. A slow provider could therefore dominate redirect latency.

**Current status:** Resolved on `dev`. Results are normalized and cached under hashed IP keys for 24 hours, failures are cached briefly, and API calls use a strict two-second ceiling. Focused tests verify cache hits, bounded fetches, private cache keys, and stable failure output.

## PHP 8.3 and 8.5 compatibility findings

### COMP-01: PHP 8.5 nullable string-function deprecations are present on `main`

**Severity:** High  
**Evidence:** supplied production log; `main` version of `app/controllers/LinkController.php:227` passes a nullable Accept-Language value to `substr`, and `main` version at `app/controllers/LinkController.php:291` passes nullable URL type data to `preg_match`.

The supplied log contains repeated `preg_match(): Passing null` and `substr(): Passing null` errors during ordinary redirect traffic. Missing browser headers and nullable legacy database fields make both conditions normal, not exceptional.

**Impact:** PHP 8.5 emits large volumes of error logs on a hot redirect path. This increases disk I/O, log-processing cost, and alert noise, and it obscures real failures. A future PHP release can turn long-deprecated behavior into a hard error.

**Recommended remediation:** Normalize optional request and model values at typed boundaries, execute regression tests with missing headers and null URL types, and run the first-party suite with `E_ALL` and deprecations converted to failures on PHP 8.3 and 8.5.

**Current status:** Resolved on `dev`. Optional request and model values are normalized before string and regular-expression operations. The complete suite passes with `E_ALL` on PHP 8.3.29 and PHP 8.5.7.

### COMP-02: PHP 8.5 deprecated cURL close calls are present on `main`

**Severity:** High  
**Evidence:** `main` version of `core/Http.class.php:215` and `core/Http.class.php:272`; the same pattern was identified in `app/helpers/Autoupdate.php`, `app/helpers/Slack.php`, and `app/helpers/GoogleTranslate.php` during the compatibility sweep.

Explicitly closing `CurlHandle` objects is deprecated on PHP 8.5. These paths are used by shared HTTP, update, Slack, and translation functions.

**Impact:** Calls generate deprecation output under PHP 8.5 and can fail strict CI that converts deprecations to test failures. Suppressing the messages would hide rather than solve the compatibility defect.

**Recommended remediation:** Remove explicit `curl_close` calls and allow handle objects to be released by scope, while preserving error capture before release. Exercise every transport path under `E_ALL` on both supported runtimes.

**Current status:** Resolved on `dev`. First-party deprecated cURL close calls are removed, and every audited cURL transport keeps TLS peer verification enabled.

### COMP-03: Composer retains an abandoned PayPal SDK

**Severity:** High  
**Evidence:** `composer.json:14`; `composer.lock:707-747`; fresh `php /tmp/composer-2.10.2.phar audit --no-interaction --format=plain` output on 2026-07-16.

Composer reports no known vulnerability advisories and one abandoned package: `paypal/rest-api-sdk-php`, with `paypal/paypal-server-sdk` named as the replacement. The release baseline also contained abandoned or obsolete Facebook and Google Authenticator dependencies, but those removals on `dev` are not yet merged.

**Impact:** The abandoned SDK receives no maintenance for new PHP behavior, API changes, or newly disclosed defects. It is already a blocker for treating PHP 8.5 support as sustainable.

**Recommended remediation:** Migrate the existing PayPal API integration to the maintained PayPal Server SDK or a small, tested first-party REST v2 client. Preserve checkout, capture, refund, and webhook behavior. Remove the abandoned package, update the lock file, and require `composer audit --abandoned=fail` in CI.

**Current status:** Resolved on `dev`. The abandoned PayPal REST SDK is removed and the maintained local REST client uses verified TLS and bounded requests. Composer reports no abandoned packages or advisories.

### COMP-04: PHP runtime policy and compatibility coverage are not yet release-proven

**Severity:** Medium  
**Evidence:** `composer.json:15`, `composer.json:22`, `phpunit.xml.dist`, `.github/workflows/php.yml`, and tests under `tests/Compatibility/`.

The `dev` manifest declares PHP `^8.3` and PHPUnit `^12.5`, and compatibility tests cover request normalization, URL targeting, OAuth, TOTP, helpers, cURL lifecycle, schema reruns, and both supported runtimes. The release baseline `main` has no PHP requirement and still includes legacy dependencies until the final merge.

**Impact:** A partial green test run can create false confidence while untested controllers, third-party providers, or production-only schema paths still fail on PHP 8.3 or 8.5.

**Recommended remediation:** Run `composer validate --strict`, clean install, full PHPUnit, first-party PHP lint with `E_ALL`, Composer audit with abandoned packages failing, and targeted payment/webhook tests on both PHP 8.3 and 8.5. CI must use the committed lock file and no platform emulation that hides runtime incompatibility.

**Current status:** Resolved on `dev`. The Composer platform floor is PHP 8.3, the lock resolves on PHP 8.3 and 8.5, and the complete automated suite is run on both runtimes before merge.

## Lower-confidence observations requiring targeted validation

These items are not promoted to confirmed vulnerabilities because exploitability depends on deployment or vendor behavior that was unavailable during static review.

- `app/helpers/App.php:138-286` and update-related code trust responses from GemPixel endpoints for validation and update workflows. Before enabling automatic updates, capture the exact response path and verify whether remote content can influence SQL, filesystem writes, or executable code. Require signed release manifests and fail closed on signature mismatch.
- No application-level CSP is present. Adding a strict policy immediately would break extensive inline theme scripts. Inventory inline scripts and third-party origins before introducing a nonce-based CSP in report-only mode.
- Database indexes should be based on production `EXPLAIN` plans and cardinality. Candidate hot predicates include `stats(urluserid,date)`, `stats(urlid,ip)`, `payment(userid,trial_days)`, `url(userid,date)`, and exact normalized report destination keys.

## Remediation order

1. Block release until SEC-01 and SEC-02 fail closed and have provider-event idempotency tests.
2. Implement the shared SSRF policy and bounded HTTP defaults from SEC-03.
3. Convert all state mutations to non-GET methods and replace the nonce boundary from SEC-04.
4. Disable or harden backup restore from SEC-05 before exposing the admin panel to untrusted networks.
5. Complete the PayPal SDK migration and run the PHP 8.3 and 8.5 release matrix.
6. Correct quota and statistics caching, then batch the confirmed N+1 paths.
7. Harden cookies, trusted-proxy handling, upload inspection, and exact report matching.

## Verification required before closure

- Every security finding needs a regression test that fails against the vulnerable behavior and passes against the final implementation.
- Webhook tests must cover missing signatures, invalid signatures, repeated events, wrong merchant, wrong currency, wrong amount, pending and partial states, refund transitions, and concurrent delivery.
- SSRF tests must cover IPv4, IPv6, decimal or encoded hosts, redirects, DNS rebinding controls, localhost, private ranges, link-local addresses, and cloud metadata endpoints.
- Route tests must prove mutation endpoints reject GET and require authenticated authorization plus CSRF validation.
- Restore tests must reject objects, unknown tables, oversized payloads, malformed rows, and archive traversal.
- Performance tests must record query counts and prove cache keys are scoped and quota counters advance atomically.
- Run the complete Composer, lint, PHPUnit, PHP 8.3, PHP 8.5, CodeGraph, tracked-secret, and `git diff main...dev` gates defined in `.agent/PLANS.md` after all workstreams stop changing.

## Audit limitations

This was a source and dependency audit, not a production penetration test. It did not include live infrastructure configuration, reverse-proxy rules, database execution plans, actual payment-provider credentials, production webhook samples, or historical Git secret scanning beyond current tracked paths. Severity can increase when those deployment facts are known. No finding is marked fixed solely because a patch or test exists on `dev`.
