# Dependency and Frontend Runtime Modernization Plan

> **For agentic workers:** Use test-driven development and isolated ownership. Do not merge version bumps without behavior-level compatibility tests.

**Goal:** Remove unsupported browser code, update every relevant first-party dependency to the newest stable release compatible with PHP 8.3, preserve existing application behavior, and make dependency drift reproducible and auditable.

**Version policy:** Use stable releases only. PHP production packages must support PHP 8.3 and PHP 8.5. Development packages may not raise the project minimum above PHP 8.3. A major upgrade must include call-site migration and tests. Abandoned libraries are replaced, not merely pinned. Browser assets are self-hosted and versioned where practical.

**Status:** Complete. The implementation is verified by the dependency policy gate, npm audit, reproducible browser asset checks, and both PHP runtime suites. PHPUnit 12.5 remains the PHP 8.3-compatible test line, while PHP 8.5 also runs PHPUnit 13.2.4.

## Task 1: Inventory and evidence

- [ ] Record Composer direct and transitive versions, abandoned packages, advisories, and latest compatible releases.
- [ ] Record bundled JavaScript, CSS, CDN, editor, icon, chart, date, autocomplete, and admin-shell versions.
- [ ] Add deterministic dependency inventory and stale-version checks.
- [ ] Fail the release gate on EOL, discontinued, deprecated, abandoned, unowned, or unexplained embedded runtime code.

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
- [ ] Upgrade to Bootstrap 5.3.8, Popper 2.11.8, jQuery 4.0.0, Jodit 4.13.5, Font Awesome 7.3.1, and the current stable chart, map, editor, clipboard, syntax, and icon assets used by the application.
- [ ] Replace Font Awesome Iconpicker with an accessible searchable Font Awesome 7 picker, Moment and both date plugins with Air Datepicker, Spectrum with Coloris, jQuery Mask with IMask, and the legacy font selector with a maintained native searchable selector.
- [ ] Replace or rebuild AdminKit bundles so no loaded asset embeds old Bootstrap, Chart.js, Feather, jsVectorMap, core-js, or duplicate jQuery runtimes.
- [ ] Remove duplicate framework payloads, parallel unmanaged copies, stale source maps, and unused representations, then load production-minified assets once.
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
