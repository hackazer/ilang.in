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
| Editor | Jodit 4.13.5, MIT, self-hosted; both `editor` and `simpleeditor` resolve to Jodit assets |
| Public runtime | Bootstrap 5.3.8, jQuery 4.0.0, Popper 2.11.8, Select2 4.1.0, clipboard 2.0.11, Feather 4.29.2, jsVectorMap 1.7.0 |
| Charts and dates | Chart.js 4.5.1 and Air Datepicker 3.6.0; Moment and both legacy date plugins removed |
| Other managed browser assets | Ace 1.44.0, Highlight.js CDN assets 11.11.1, Tagify 4.38.0, Devbridge Autocomplete 2.0.4, Cookie Consent 3.1.1, Coloris 0.25.0, BlockAdBlock 3.2.1, Font Awesome 7.3.1, SVG Injector 1.1.3, IMask 7.6.1 |
| Admin shell | AdminKit 3.4.0 styles rebuilt with Bootstrap 5.3.8; legacy AdminKit JavaScript and embedded old runtimes removed |
| Addons | no installed plugin manifest; `default` and `ilangin-child` themes both report version 1.0 |

Representative call-site evidence includes TwitterOAuth in `app/controllers/UsersController.php`, QR Code in `app/helpers/QrGd.php`, Monolog in `core/GemError.class.php`, Stripe in `app/helpers/payments/Stripe.php`, Jodit through `app/config/cdn.php`, admin-shell loads in both theme layout trees, and plugin discovery in `app/controllers/admin/PluginsController.php`. The generated inventory contains the complete sorted call-site column.

## Compatibility policy

No EOL, discontinued, abandoned, or unexplained compatibility hold remains in the release policy. PHPUnit is the only runtime matrix item because its latest major requires a newer PHP runtime: PHP 8.3 uses the actively supported 12.5 line, and PHP 8.5 runs 13.2.4 in CI.

## Replaced legacy assets

- `endroid/qr-code` was replaced by `chillerlan/php-qrcode` 6.0.1, which supports the PHP 8.3 floor and preserves GD, SVG, PDF, logo, file, and data URI output.
- Discontinued Font Awesome Iconpicker was replaced by an accessible searchable Font Awesome 7 picker.
- Moment and the legacy datepicker and daterangepicker stack were replaced by Air Datepicker.
- Spectrum was replaced by Coloris, jQuery Mask by IMask, and the legacy font selector by a first-party searchable selector.
- The distributed AdminKit JavaScript bundle was removed. AdminKit SCSS is rebuilt with current Bootstrap and the current standalone chart and map assets are loaded explicitly.
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

The generated Markdown report includes the exact official metadata URL for every resolved package. The browser and Composer manifests are now committed, `npm outdated --json` returns no outdated root browser packages, and Composer reports only PHPUnit 13.2.4 as newer than the PHP 8.3-compatible PHPUnit 12.5.31 line. The release gate rechecks this policy on every release.
