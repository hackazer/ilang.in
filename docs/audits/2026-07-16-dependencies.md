# Dependency audit

Baseline: 2026-07-16
Metadata refresh: 2026-07-17, Asia/Jakarta

## Scope

`scripts/dependency-inventory.sh` inventories:

- Composer direct and transitive packages from `composer.json` and `composer.lock`
- root npm runtime and build dependencies from `package.json` and `package-lock.json`
- browser versions, licenses, bundles, and hashes represented by `public/static/vendor-manifest.json`
- self-hosted and remote definitions in `app/config/cdn.php`, parsed as text without executing PHP
- the AdminKit shell and its separately locked embedded packages
- plugin and theme manifests
- actual first-party namespace, asset-path, CDN-loader, and layout call sites
- duplicate package candidates, parallel source/minified artifacts, unknown versions, missing licenses, deprecation, discontinuation, abandonment, and compatibility holds

Default output is offline, sorted, timestamp-free, and network-free. Upstream stable values appear only when official metadata is supplied or `--online` is explicit.

## Refresh commands

Deterministic repository snapshot:

```sh
sh scripts/dependency-inventory.sh
sh scripts/dependency-inventory.sh --format markdown
```

Official Packagist and npm stable-version refresh:

```sh
sh scripts/dependency-inventory.sh --online --format markdown \
  > /tmp/dependency-release-table.md
```

Deterministic metadata-cache run:

```sh
sh scripts/dependency-inventory.sh \
  --metadata-dir /path/to/metadata-cache \
  --format markdown
```

Release gate after all findings are intentionally resolved:

```sh
sh scripts/dependency-inventory.sh --fail-on-findings
```

`--fail-on-findings` exits 2 when findings remain. The default inventory still exits zero so CI can archive and review the report before enforcing policy.

## Current managed state

The browser state below is sourced from the active root npm lock, vendor manifest, and package headers. Re-run the commands after the browser modernization commit because that workstream was still changing while this report was prepared.

| Surface | Current evidence |
| --- | --- |
| Composer | 12 direct packages and 31 transitive packages are locked; no lock entry reports Composer abandonment |
| Editor | Jodit 4.13.3, MIT, self-hosted; both `editor` and `simpleeditor` resolve to Jodit assets |
| Public runtime | Bootstrap 4.6.2, jQuery 3.7.1, Popper 1.16.1, Select2 4.1.0, clipboard 2.0.11, Feather 4.29.2, jsVectorMap 1.7.0 |
| Charts and dates | Chart.js 4.5.1, datepicker 1.0.10, daterangepicker 3.1.0, Moment 2.30.1 |
| Other managed browser assets | Ace 1.44.0, Highlight.js CDN assets 11.11.1, Tagify 4.38.0, Devbridge Autocomplete 2.0.4, Cookie Consent 3.1.1, Spectrum 1.8.1, BlockAdBlock 3.2.1, Fontselect 1.1.0, SVG Injector 1.1.3, jQuery Mask 1.14.16 |
| Admin shell | AdminKit 3.4.0, MIT; embedded Bootstrap 5.3.0, Chart.js 2.9.4, Feather 4.29.0, and jsVectorMap 1.5.3 are separate lock rows |
| Addons | no installed plugin manifest; `default` and `ilangin-child` themes both report version 1.0 |

Representative call-site evidence includes TwitterOAuth in `app/controllers/UsersController.php`, QR Code in `app/helpers/QrGd.php`, Monolog in `core/GemError.class.php`, Stripe in `app/helpers/payments/Stripe.php`, Jodit through `app/config/cdn.php`, admin-shell loads in both theme layout trees, and plugin discovery in `app/controllers/admin/PluginsController.php`. The generated inventory contains the complete sorted call-site column.

## Deliberate compatibility holds

| Dependency | Current | Official latest stable | Exact blocker |
| --- | ---: | ---: | --- |
| `endroid/qr-code` | 6.0.9 | 6.1.3 | Packagist metadata for 6.1.3 requires PHP `^8.4`; project minimum is PHP 8.3 |
| `phpunit/phpunit` | 12.5.31 | 13.2.4 | Packagist metadata for 13.2.4 requires PHP `>=8.4.1`; the release gate must still run on PHP 8.3 |
| `jquery` | 3.7.1 | 4.0.0 | Bootstrap 4.6.2 declares the jQuery peer range `1.9.1 - 3` |
| public `bootstrap` | 4.6.2 | 5.3.8 | inherited public templates use Bootstrap 4 data APIs and utility classes; this requires a full theme migration |
| `@adminkit/core` | 3.4.0 | 3.4.0 | current shell is pinned as a unit; its embedded dependency versions are inventoried separately |

These are compatibility holds, not upgrade omissions. The script flags Bootstrap, jQuery, AdminKit, and PHPUnit directly. Online metadata also flags any Composer latest release whose minimum PHP version exceeds the project minimum.

## Remaining unresolved or discontinued assets

- `fontawesome-iconpicker` is pinned at final stable 3.2.0 under MIT. Official npm metadata marks the project discontinued. Existing admin-plan and bio-editor jQuery call sites block simple removal, so replacement requires call-site migration and behavior tests.
- Moment 2.30.1 is current, but the official project documentation describes Moment as a legacy project in maintenance mode. It remains a daterangepicker dependency and should not be added to new code.
- First-party or ownership-unresolved standalone files such as `animate`, `bio`, `bookmarklet`, `bundle.pack`, `chart-compat`, `charts`, `custom`, `detect.app`, `editor-adapter`, and `server` have no independent package version or license marker. The inventory reports them instead of guessing provenance.
- The `default` and `ilangin-child` theme manifests contain no license field.
- Parallel source and minified files are reported separately from runtime duplication. Their presence is packaging evidence, not proof that both execute on one route.
- jQuery appears in the public bundle and the vendored library tree. The inventory labels this a duplicate candidate but does not claim both copies load on every page.

No live CKEditor asset is assumed. CKEditor lifecycle classification is URL-driven and retained only in an isolated regression fixture to prove that a genuine CKEditor definition is reported as EOL.

## Evidence sources

- Composer metadata: `https://repo.packagist.org/p2/{vendor}/{package}.json`
- npm metadata: `https://registry.npmjs.org/{package}`
- CKEditor 4 lifecycle: <https://ckeditor.com/ckeditor-4/>
- Moment project status: <https://momentjs.com/docs/#/-project-status/>

The generated Markdown report includes the exact official metadata URL for every resolved package. Refresh that report after the browser and Composer manifests are committed, then use it as the release table.
