# Dependency and Frontend Runtime Modernization Plan

> **For agentic workers:** Use test-driven development and isolated ownership. Do not merge version bumps without behavior-level compatibility tests.

**Goal:** Remove unsupported browser code, update every relevant first-party dependency to the newest stable release compatible with PHP 8.3, preserve existing application behavior, and make dependency drift reproducible and auditable.

**Version policy:** Use stable releases only. PHP production packages must support PHP 8.3 and PHP 8.5. Development packages may not raise the project minimum above PHP 8.3. A major upgrade must include call-site migration and tests. Abandoned libraries are replaced, not merely pinned. Browser assets are self-hosted and versioned where practical.

## Task 1: Inventory and evidence

- [ ] Record Composer direct and transitive versions, abandoned packages, advisories, and latest compatible releases.
- [ ] Record bundled JavaScript, CSS, CDN, editor, icon, chart, date, autocomplete, and admin-shell versions.
- [ ] Add deterministic dependency inventory and stale-version checks.
- [ ] Document deliberate compatibility holds with exact blockers and replacement plans.

## Task 2: Replace CKEditor 4

- [ ] Replace CKEditor 4.16.1 and its global API with current stable Jodit under the MIT license.
- [ ] Self-host pinned editor assets and remove CKEditor CDN references.
- [ ] Preserve textarea synchronization, dynamic editor creation/destruction, source editing, tables, links, images, and existing HTML.
- [ ] Update admin, blog, page, FAQ, theme, email, and bio editor integrations.
- [ ] Add editor markup, lifecycle, form synchronization, and no-CKEditor regression tests.

## Task 3: Modernize Composer dependencies

- [ ] Upgrade direct packages to the newest stable versions compatible with PHP 8.3.
- [ ] Migrate changed Twitter, QR, logging, cache, mail, Stripe, and PHPUnit APIs.
- [ ] Run strict Composer validation, clean install, audit with abandoned packages failing, platform checks, and full tests on PHP 8.3 and 8.5.

## Task 4: Modernize browser dependencies

- [ ] Add a reproducible browser dependency manifest and lock file.
- [ ] Update jQuery, Bootstrap, Chart.js, Select2, clipboard, Feather, jsVectorMap, Ace, Highlight.js, Moment, and date-range assets where used.
- [ ] Replace abandoned Bootstrap plugins or pin only when a tested compatibility boundary requires a staged migration.
- [ ] Remove duplicate framework payloads and load production-minified assets once.
- [ ] Add static asset version, integrity, duplicate-load, and compatibility tests.

## Task 5: Performance and query release gate

- [ ] Run request-path query-count tests and inspect remaining N+1 findings.
- [ ] Verify payment reconciliation batches, click quota locking, dashboard batching, cache keys, and retry behavior.
- [ ] Compare first-party asset bytes and request counts before and after modernization.
- [ ] Run source audit, secret scan, conflict-marker scan, lint, and full tests.

## Task 6: Final review and release

- [ ] Synchronize CodeGraph and review `main...dev`.
- [ ] Obtain independent payment, security, dependency, and compatibility reviews.
- [ ] Fix every Critical and Important finding.
- [ ] Update audit, operations guide, and this ExecPlan with exact evidence.
- [ ] Push `dev`, merge with `--no-ff` into `main`, push `main`, and verify remote refs.
