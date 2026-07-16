#!/bin/sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
FORMAT=tsv
METADATA_DIR=
ONLINE=0
FAIL_ON_FINDINGS=0

usage() {
    cat <<'EOF'
Usage: sh scripts/dependency-inventory.sh [options]

Options:
  --root PATH             Inspect another project root, primarily for tests.
  --format tsv|markdown   Select deterministic output format. Default: tsv.
  --metadata-dir PATH     Read cached Packagist and npm JSON metadata.
  --online                Query official Packagist and npm metadata. Opt-in only.
  --fail-on-findings      Exit 2 when duplicate, unknown, or unsupported items exist.
  --help                  Show this help.

Cached metadata layout:
  composer/vendor/package.json
  npm/package-name.json
EOF
}

while [ "$#" -gt 0 ]; do
    case "$1" in
        --root)
            [ "$#" -ge 2 ] || { echo "--root requires a path" >&2; exit 64; }
            ROOT=$2
            shift 2
            ;;
        --format)
            [ "$#" -ge 2 ] || { echo "--format requires tsv or markdown" >&2; exit 64; }
            FORMAT=$2
            shift 2
            ;;
        --metadata-dir)
            [ "$#" -ge 2 ] || { echo "--metadata-dir requires a path" >&2; exit 64; }
            METADATA_DIR=$2
            shift 2
            ;;
        --online)
            ONLINE=1
            shift
            ;;
        --fail-on-findings)
            FAIL_ON_FINDINGS=1
            shift
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            usage >&2
            exit 64
            ;;
    esac
done

case "$FORMAT" in
    tsv|markdown) ;;
    *) echo "Unsupported format: $FORMAT" >&2; exit 64 ;;
esac

[ -d "$ROOT" ] || { echo "Project root does not exist: $ROOT" >&2; exit 66; }
ROOT=$(CDPATH= cd -- "$ROOT" && pwd)
if [ -n "$METADATA_DIR" ]; then
    [ -d "$METADATA_DIR" ] || { echo "Metadata directory does not exist: $METADATA_DIR" >&2; exit 66; }
    METADATA_DIR=$(CDPATH= cd -- "$METADATA_DIR" && pwd)
fi

php -d display_errors=stderr -- "$ROOT" "$FORMAT" "$METADATA_DIR" "$ONLINE" "$FAIL_ON_FINDINGS" <<'PHP'
<?php

declare(strict_types=1);

[$program, $root, $format, $metadataDir, $onlineRaw, $failRaw] = $argv;
$online = $onlineRaw === '1';
$failOnFindings = $failRaw === '1';
$rows = [];
$sourceFiles = [];
$browserPackages = [];
$inventoriedNpm = [];
$findings = 0;

function relativePath(string $root, string $path): string
{
    return ltrim(str_replace('\\', '/', substr($path, strlen($root))), '/');
}

function jsonFile(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        return null;
    }
    try {
        $value = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return null;
    }
    return is_array($value) ? $value : null;
}

function collectFiles(string $root, array $directories, array $extensions): array
{
    $files = [];
    foreach ($directories as $directory) {
        $path = $directory === '.' ? $root : $root.'/'.$directory;
        if (is_file($path)) {
            $files[] = $path;
            continue;
        }
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $entry) {
            if (!$entry->isFile()) {
                continue;
            }
            $relative = relativePath($root, $entry->getPathname());
            if (str_starts_with($relative, 'storage/plugins/') || str_starts_with($relative, 'storage/addons/')) {
                continue;
            }
            if (in_array(strtolower($entry->getExtension()), $extensions, true)) {
                $files[] = $entry->getPathname();
            }
        }
    }
    sort($files, SORT_STRING);
    return array_values(array_unique($files));
}

function sourceLines(array $files, string $root): array
{
    $lines = [];
    foreach ($files as $file) {
        $contents = file($file, FILE_IGNORE_NEW_LINES);
        if ($contents === false) {
            continue;
        }
        foreach ($contents as $index => $line) {
            $lines[] = [relativePath($root, $file), $index + 1, $line];
        }
    }
    return $lines;
}

function callSites(array $sourceLines, array $needles): string
{
    $matches = [];
    foreach ($needles as $needle) {
        if ($needle === '') {
            continue;
        }
        foreach ($sourceLines as [$file, $line, $contents]) {
            if (str_contains($contents, $needle)) {
                $matches[$file.':'.$line] = true;
            }
        }
    }
    $paths = array_keys($matches);
    sort($paths, SORT_STRING);
    return $paths === [] ? '-' : implode(';', array_slice($paths, 0, 30));
}

function stableVersion(string $version): bool
{
    return preg_match('/(?:dev|alpha|beta|rc|snapshot)/i', $version) !== 1;
}

function minimumConstraintVersion(string $constraint): ?string
{
    if (preg_match('/(?<![0-9])([0-9]+\.[0-9]+(?:\.[0-9]+)?)/', $constraint, $match) !== 1) {
        return null;
    }
    return substr_count($match[1], '.') === 1 ? $match[1].'.0' : $match[1];
}

function npmLockMetadata(array $lock, string $name): array
{
    $package = $lock['packages']['node_modules/'.$name] ?? null;
    if (!is_array($package)) {
        return ['version' => '-', 'license' => '-'];
    }
    $license = $package['license'] ?? '-';
    if (is_array($license)) {
        $license = implode(',', $license);
    }
    return [
        'version' => (string) ($package['version'] ?? '-'),
        'license' => (string) $license ?: '-',
    ];
}

function officialMetadata(string $ecosystem, string $name, string $metadataDir, bool $online): array
{
    $url = $ecosystem === 'composer'
        ? 'https://repo.packagist.org/p2/'.$name.'.json'
        : 'https://registry.npmjs.org/'.str_replace('%2F', '%2f', rawurlencode($name));
    $cache = $metadataDir === '' ? '' : $metadataDir.'/'.$ecosystem.'/'.$name.'.json';
    $document = $cache !== '' ? jsonFile($cache) : null;

    if ($document === null && $online) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 12,
                'header' => "Accept: application/json\r\nUser-Agent: ilang.in-dependency-inventory/1\r\n",
            ],
        ]);
        $contents = @file_get_contents($url, false, $context);
        if ($contents !== false) {
            try {
                $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                $document = is_array($decoded) ? $decoded : null;
            } catch (JsonException) {
                $document = null;
            }
        }
    }

    if ($document === null) {
        return ['latest' => '-', 'license' => '-', 'status' => 'unknown', 'url' => $url, 'requires_php' => '-'];
    }

    if ($ecosystem === 'composer') {
        $versions = $document['packages'][$name] ?? [];
        foreach ($versions as $version) {
            $number = (string) ($version['version'] ?? '');
            if ($number === '' || !stableVersion($number)) {
                continue;
            }
            $license = $version['license'] ?? [];
            $licenseText = is_array($license) ? implode(',', $license) : (string) $license;
            $abandoned = $version['abandoned'] ?? false;
            $status = $abandoned === false ? 'active' : 'abandoned:'.(is_string($abandoned) ? $abandoned : 'yes');
            return [
                'latest' => ltrim($number, 'v'),
                'license' => $licenseText ?: '-',
                'status' => $status,
                'url' => $url,
                'requires_php' => (string) ($version['require']['php'] ?? '-'),
            ];
        }
        return ['latest' => '-', 'license' => '-', 'status' => 'unknown', 'url' => $url, 'requires_php' => '-'];
    }

    $latest = (string) ($document['dist-tags']['latest'] ?? '');
    if ($latest === '' || !stableVersion($latest)) {
        $candidates = array_values(array_filter(
            array_keys(is_array($document['versions'] ?? null) ? $document['versions'] : []),
            static fn (string $version): bool => stableVersion($version) && preg_match('/^v?[0-9]+\.[0-9]+(?:\.[0-9]+)?$/', $version) === 1,
        ));
        usort($candidates, static fn (string $left, string $right): int => version_compare(ltrim($right, 'v'), ltrim($left, 'v')));
        $latest = $candidates[0] ?? '';
    }
    $version = is_array($document['versions'][$latest] ?? null) ? $document['versions'][$latest] : [];
    $license = $version['license'] ?? '-';
    if (is_array($license)) {
        $license = implode(',', array_map(static fn ($item): string => is_array($item) ? (string) ($item['type'] ?? '-') : (string) $item, $license));
    }
    $deprecated = trim((string) ($version['deprecated'] ?? ''));
    return [
        'latest' => $latest !== '' ? ltrim($latest, 'v') : '-',
        'license' => (string) $license ?: '-',
        'status' => $deprecated === '' ? 'active' : 'deprecated:'.$deprecated,
        'url' => $url,
        'requires_php' => '-',
    ];
}

function addRow(array &$rows, array $row): void
{
    $defaults = [
        'kind' => '-', 'name' => '-', 'relationship' => '-', 'current' => '-',
        'constraint' => '-', 'latest' => '-', 'license' => '-', 'status' => 'unknown',
        'source' => '-', 'call_sites' => '-', 'flags' => '-', 'metadata_url' => '',
    ];
    $row = array_merge($defaults, $row);
    foreach ($row as $key => $value) {
        $row[$key] = trim(str_replace(["\t", "\r", "\n"], ' ', (string) $value));
        if ($row[$key] === '') {
            $row[$key] = '-';
        }
    }
    $rows[] = $row;
}

function addFinding(array &$rows, int &$findings, string $name, string $source, string $status, string $flags): void
{
    ++$findings;
    addRow($rows, [
        'kind' => 'finding',
        'name' => $name,
        'relationship' => 'review',
        'status' => $status,
        'source' => $source,
        'flags' => $flags,
    ]);
}

function flags(array $items): string
{
    $items = array_values(array_unique(array_filter($items, static fn (string $item): bool => $item !== '')));
    sort($items, SORT_STRING);
    return $items === [] ? '-' : implode(',', $items);
}

function versionFromContents(string $name, array $files): string
{
    $patterns = [
        'jquery' => '/jQuery(?: JavaScript Library)? v([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'bootstrap' => '/Bootstrap v([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'select2' => '/Select2 ([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'clipboard' => '/clipboard\.js v([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'feather-icons' => '/feather(?: icons)? v?([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'bootstrap-notify' => '/Bootstrap Notify\s*=\s*v([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'bootstrap-tagsinput' => '/bootstrap-tagsinput v?([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'jquery-mask-plugin' => '/jQuery Mask Plugin v?([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'fontawesome-picker' => '/fontawesome(?:-icon)?picker v?([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'svg-injector' => '/SVGInjector v?([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'chart.js' => '/Chart\.js v([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'cookieconsent' => '/cookieconsent(?:\.min)?\.js v?([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
        'jodit' => '/jodit.*?Version:\s*v?([0-9]+\.[0-9]+(?:\.[0-9]+)?)/is',
    ];
    $pattern = $patterns[$name] ?? null;
    if ($pattern === null) {
        return '-';
    }
    foreach ($files as $file) {
        $contents = file_get_contents($file, false, null, 0, 262144);
        if ($contents !== false && preg_match($pattern, $contents, $match) === 1) {
            return $match[1];
        }
    }
    return '-';
}

function assetFiles(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $entry) {
        if ($entry->isFile() && in_array(strtolower($entry->getExtension()), ['js', 'css', 'json'], true)) {
            $files[] = $entry->getPathname();
        }
    }
    sort($files, SORT_STRING);
    return $files;
}

$sourceFiles = collectFiles($root, ['app', 'core', 'storage/themes', 'index.php'], ['php', 'html', 'js', 'css']);
$sourceLines = sourceLines($sourceFiles, $root);
$rootNpm = jsonFile($root.'/package.json') ?? [];
$rootNpmRuntime = is_array($rootNpm['dependencies'] ?? null) ? $rootNpm['dependencies'] : [];
$rootNpmDev = is_array($rootNpm['devDependencies'] ?? null) ? $rootNpm['devDependencies'] : [];
$rootNpmDependencies = array_merge($rootNpmRuntime, $rootNpmDev);
$npmLock = jsonFile($root.'/package-lock.json') ?? [];
$vendorManifest = jsonFile($root.'/public/static/vendor-manifest.json') ?? [];
$manifestVersions = is_array($vendorManifest['versions'] ?? null) ? $vendorManifest['versions'] : [];
$manifestHolds = array_merge(
    is_array($rootNpm['browserCompatibility']['holds'] ?? null) ? $rootNpm['browserCompatibility']['holds'] : [],
    is_array($vendorManifest['holds'] ?? null) ? $vendorManifest['holds'] : [],
);

// Composer inventory. The lock file is authoritative for installed versions.
$composer = jsonFile($root.'/composer.json') ?? [];
$lock = jsonFile($root.'/composer.lock') ?? [];
$projectPhpMinimum = minimumConstraintVersion((string) ($composer['require']['php'] ?? ''));
$directRuntime = array_diff_key(is_array($composer['require'] ?? null) ? $composer['require'] : [], array_flip(['php']));
$directRuntime = array_filter($directRuntime, static fn (mixed $constraint, string $name): bool => !str_starts_with($name, 'ext-'), ARRAY_FILTER_USE_BOTH);
$directDev = is_array($composer['require-dev'] ?? null) ? $composer['require-dev'] : [];

foreach ([['packages', 'runtime'], ['packages-dev', 'dev']] as [$lockKey, $scope]) {
    foreach (is_array($lock[$lockKey] ?? null) ? $lock[$lockKey] : [] as $package) {
        $name = (string) ($package['name'] ?? 'unknown');
        $directSet = $scope === 'runtime' ? $directRuntime : $directDev;
        $isDirect = array_key_exists($name, $directSet);
        $relationship = ($isDirect ? 'direct-' : 'transitive-').$scope;
        $constraint = $isDirect ? (string) $directSet[$name] : '-';
        $license = $package['license'] ?? [];
        $licenseText = is_array($license) ? implode(',', $license) : (string) $license;
        $metadata = officialMetadata('composer', $name, $metadataDir, $online);
        $abandoned = $package['abandoned'] ?? false;
        $status = $abandoned === false ? $metadata['status'] : 'abandoned:'.(is_string($abandoned) ? $abandoned : 'yes');
        $needles = [];
        foreach (['psr-4', 'psr-0'] as $autoloadType) {
            foreach (array_keys(is_array($package['autoload'][$autoloadType] ?? null) ? $package['autoload'][$autoloadType] : []) as $prefix) {
                $needles[] = rtrim((string) $prefix, '\\').'\\';
            }
        }
        $rowFlags = [];
        $current = ltrim((string) ($package['version'] ?? '-'), 'v');
        if ($abandoned !== false || str_starts_with($metadata['status'], 'abandoned:')) {
            $rowFlags[] = 'abandoned';
        }
        if ($metadata['latest'] !== '-' && $current !== '-' && version_compare($current, $metadata['latest'], '<')) {
            $rowFlags[] = 'outdated';
        }
        $latestPhpMinimum = minimumConstraintVersion($metadata['requires_php']);
        if ($projectPhpMinimum !== null && $latestPhpMinimum !== null && version_compare($latestPhpMinimum, $projectPhpMinimum, '>')) {
            $rowFlags[] = 'compatibility-hold';
        }
        if ($name === 'phpunit/phpunit' && $projectPhpMinimum !== null && version_compare($projectPhpMinimum, '8.4.1', '<') && version_compare($current, '13.0.0', '<')) {
            $rowFlags[] = 'compatibility-hold';
        }
        if ($licenseText === '') {
            $rowFlags[] = 'license-unknown';
        }
        addRow($rows, [
            'kind' => 'composer', 'name' => $name, 'relationship' => $relationship,
            'current' => $current, 'constraint' => $constraint, 'latest' => $metadata['latest'],
            'license' => $licenseText ?: $metadata['license'], 'status' => $status,
            'source' => 'composer.lock', 'call_sites' => callSites($sourceLines, $needles),
            'flags' => flags($rowFlags), 'metadata_url' => $metadata['url'],
        ]);
    }
}

// Vendored browser packages.
$libraryRoot = $root.'/public/static/frontend/libs';
$npmNames = [
    'bootstrap' => 'bootstrap', 'bootstrap-notify' => 'bootstrap-notify',
    'bootstrap-tagsinput' => 'bootstrap-tagsinput', 'clipboard' => 'clipboard',
    'feather-icons' => 'feather-icons', 'font-selector' => 'fontselect-jquery-plugin',
    'fontawesome-picker' => 'fontawesome-iconpicker', 'jquery' => 'jquery',
    'jquery-mask-plugin' => 'jquery-mask-plugin', 'jsvectormap' => 'jsvectormap',
    'select2' => 'select2', 'svg-injector' => 'svg-injector',
    'ace-builds' => 'ace-builds', 'datepicker' => '@chenfengyuan/datepicker',
    'daterangepicker' => 'daterangepicker', 'devbridge-autocomplete' => 'devbridge-autocomplete',
    'highlight.js' => '@highlightjs/cdn-assets', 'moment' => 'moment',
    'spectrum' => 'spectrum-colorpicker', 'tagify' => '@yaireo/tagify',
];
if (is_dir($libraryRoot)) {
    $directories = array_values(array_filter(scandir($libraryRoot) ?: [], static fn (string $name): bool => $name !== '.' && $name !== '..'));
    sort($directories, SORT_STRING);
    foreach ($directories as $directoryName) {
        $directory = $libraryRoot.'/'.$directoryName;
        if (!is_dir($directory)) {
            continue;
        }
        $files = assetFiles($directory);
        $manifestPath = $directory.'/package.json';
        $manifest = jsonFile($manifestPath);
        $packageName = (string) ($manifest['name'] ?? ($npmNames[$directoryName] ?? $directoryName));
        $lockedNpm = npmLockMetadata($npmLock, $packageName);
        $version = (string) ($manifestVersions[$packageName] ?? ($rootNpmDependencies[$packageName] ?? ($manifest['version'] ?? $lockedNpm['version'])));
        if ($version === '-') {
            $version = versionFromContents($directoryName, $files);
        }
        $license = $manifest['license'] ?? $lockedNpm['license'];
        if (is_array($license)) {
            $license = implode(',', $license);
        }
        $metadata = officialMetadata('npm', $packageName, $metadataDir, $online);
        $rowFlags = [];
        if ($version === '-') {
            $rowFlags[] = 'unversioned';
        }
        if ($license === '-') {
            $license = $metadata['license'];
        }
        if ($license === '-') {
            $rowFlags[] = 'license-unknown';
        }
        if ($metadata['latest'] !== '-' && $version !== '-' && version_compare(ltrim($version, 'v'), ltrim($metadata['latest'], 'v'), '<')) {
            $rowFlags[] = 'outdated';
        }
        if (($packageName === 'jquery' && isset($manifestHolds['jquery'])) || ($packageName === 'bootstrap' && isset($manifestHolds['publicBootstrap']))) {
            $rowFlags[] = 'compatibility-hold';
        }
        $status = $metadata['status'];
        if ($packageName === 'fontawesome-iconpicker' && isset($manifestHolds['fontawesomeIconpicker'])) {
            $rowFlags[] = 'compatibility-hold';
            $rowFlags[] = 'discontinued';
            $status = 'discontinued';
        }
        if ($status === 'unknown' && (isset($rootNpmDependencies[$packageName]) || isset($manifestVersions[$packageName]))) {
            $status = 'managed';
        }
        $source = is_file($manifestPath) ? relativePath($root, $manifestPath) : relativePath($root, $directory);
        $calls = callSites($sourceLines, ['frontend/libs/'.$directoryName.'/']);
        addRow($rows, [
            'kind' => 'browser', 'name' => $packageName, 'relationship' => 'vendored',
            'current' => ltrim($version, 'v'), 'latest' => $metadata['latest'], 'license' => (string) $license,
            'status' => $status, 'source' => $source, 'call_sites' => $calls,
            'flags' => flags($rowFlags), 'metadata_url' => $metadata['url'],
        ]);
        $browserPackages[$packageName] = ['version' => ltrim($version, 'v'), 'license' => (string) $license, 'source' => $source];
        $inventoriedNpm[$packageName] = true;

        $parallel = [];
        foreach ($files as $file) {
            $relative = relativePath($root, $file);
            if (preg_match('/^(.*?)(?:\.min)?\.(js|css)$/', $relative, $match) === 1) {
                $parallel[$match[1].'.'.$match[2]][] = $relative;
            }
        }
        foreach ($parallel as $representations) {
            if (count($representations) > 1) {
                sort($representations, SORT_STRING);
                addFinding($rows, $findings, 'parallel-browser-artifacts', implode(';', $representations), 'minified and source representations', $packageName);
            }
        }
        if ($version === '-') {
            addFinding($rows, $findings, 'unknown-or-unversioned', $source, 'version unresolved', $packageName);
        }
    }
}

// Other self-hosted third-party packages, outside the legacy frontend/libs tree.
$vendorRoot = $root.'/public/static/vendor';
if (is_dir($vendorRoot)) {
    $directories = array_values(array_filter(scandir($vendorRoot) ?: [], static fn (string $name): bool => $name !== '.' && $name !== '..'));
    sort($directories, SORT_STRING);
    foreach ($directories as $directoryName) {
        $directory = $vendorRoot.'/'.$directoryName;
        if (!is_dir($directory)) {
            continue;
        }
        $files = assetFiles($directory);
        $manifest = jsonFile($directory.'/package.json');
        $packageName = (string) ($manifest['name'] ?? $directoryName);
        $version = (string) ($manifest['version'] ?? versionFromContents($packageName, $files));
        $license = $manifest['license'] ?? '-';
        if (is_array($license)) {
            $license = implode(',', $license);
        }
        if ($license === '-' && is_file($directory.'/LICENSE.txt')) {
            $licenseContents = file_get_contents($directory.'/LICENSE.txt') ?: '';
            if (stripos($licenseContents, 'Permission is hereby granted, free of charge') !== false || stripos($licenseContents, 'MIT License') !== false) {
                $license = 'MIT';
            }
        }
        $metadata = officialMetadata('npm', $packageName, $metadataDir, $online);
        $lockedNpm = npmLockMetadata($npmLock, $packageName);
        $rowFlags = [];
        if ($version === '-') {
            $rowFlags[] = 'unversioned';
        }
        if ($license === '-') {
            $license = $lockedNpm['license'] !== '-' ? $lockedNpm['license'] : $metadata['license'];
        }
        if ($license === '-') {
            $rowFlags[] = 'license-unknown';
        }
        if ($metadata['latest'] !== '-' && $version !== '-' && version_compare(ltrim($version, 'v'), ltrim($metadata['latest'], 'v'), '<')) {
            $rowFlags[] = 'outdated';
        }
        $source = relativePath($root, $directory);
        addRow($rows, [
            'kind' => 'browser', 'name' => $packageName, 'relationship' => 'vendored',
            'current' => ltrim($version, 'v'), 'latest' => $metadata['latest'], 'license' => (string) $license,
            'status' => $metadata['status'] === 'unknown' && $version !== '-' && $license !== '-' ? 'managed' : $metadata['status'], 'source' => $source,
            'call_sites' => callSites($sourceLines, ['vendor/'.$directoryName.'/']),
            'flags' => flags($rowFlags), 'metadata_url' => $metadata['url'],
        ]);
        $browserPackages[$packageName] = ['version' => ltrim($version, 'v'), 'license' => (string) $license, 'source' => $source];
        $inventoriedNpm[$packageName] = true;
        if ($version === '-') {
            addFinding($rows, $findings, 'unknown-or-unversioned', $source, 'version unresolved', $packageName);
        }
    }
}

// Standalone top-level browser assets and embedded package fingerprints.
$staticRoot = $root.'/public/static';
$standaloneGroups = [];
if (is_dir($staticRoot)) {
    foreach (scandir($staticRoot) ?: [] as $name) {
        $path = $staticRoot.'/'.$name;
        if (!is_file($path) || !preg_match('/\.(?:js|css)$/i', $name)) {
            continue;
        }
        $key = preg_replace('/\.min(?=\.(?:js|css)$)/i', '', $name);
        $standaloneGroups[$key][] = $path;
    }
}
ksort($standaloneGroups, SORT_STRING);
foreach ($standaloneGroups as $key => $files) {
    sort($files, SORT_STRING);
    $lower = strtolower($key);
    $packageName = str_starts_with($lower, 'chart.') ? 'chart.js' : (str_starts_with($lower, 'cookieconsent.') ? 'cookieconsent' : 'asset:'.preg_replace('/\.(?:js|css)$/i', '', $key));
    $lockedNpm = npmLockMetadata($npmLock, $packageName);
    $version = (string) ($manifestVersions[$packageName] ?? ($rootNpmDependencies[$packageName] ?? versionFromContents($packageName, $files)));
    $metadata = str_starts_with($packageName, 'asset:')
        ? ['latest' => '-', 'license' => '-', 'status' => 'ownership-unresolved', 'url' => '']
        : officialMetadata('npm', $packageName, $metadataDir, $online);
    if ($metadata['license'] === '-' && $lockedNpm['license'] !== '-') {
        $metadata['license'] = $lockedNpm['license'];
    }
    if ($metadata['status'] === 'unknown' && (isset($rootNpmDependencies[$packageName]) || isset($manifestVersions[$packageName]))) {
        $metadata['status'] = 'managed';
    }
    $sourcePaths = array_map(static fn (string $file): string => relativePath($root, $file), $files);
    $needles = array_map(static fn (string $file): string => 'static/'.basename($file), $files);
    $rowFlags = [];
    if ($version === '-') {
        $rowFlags[] = 'unversioned';
    }
    if ($metadata['license'] === '-') {
        $rowFlags[] = 'license-unknown';
    }
    if (count($files) > 1) {
        $rowFlags[] = 'parallel-representations';
        addFinding($rows, $findings, 'parallel-browser-artifacts', implode(';', $sourcePaths), 'minified and source representations', $packageName);
    }
    addRow($rows, [
        'kind' => 'browser', 'name' => $packageName, 'relationship' => 'standalone',
        'current' => $version, 'latest' => $metadata['latest'], 'license' => $metadata['license'],
        'status' => $metadata['status'], 'source' => implode(';', $sourcePaths),
        'call_sites' => callSites($sourceLines, $needles), 'flags' => flags($rowFlags),
        'metadata_url' => $metadata['url'],
    ]);
    $browserPackages[$packageName] = ['version' => $version, 'license' => $metadata['license'], 'source' => implode(';', $sourcePaths)];
    if (!str_starts_with($packageName, 'asset:')) {
        $inventoriedNpm[$packageName] = true;
    }
    if ($version === '-') {
        addFinding($rows, $findings, 'unknown-or-unversioned', implode(';', $sourcePaths), 'version unresolved', $packageName);
    }

    foreach (['jquery', 'bootstrap'] as $embeddedName) {
        $embeddedVersion = versionFromContents($embeddedName, $files);
        if ($embeddedVersion !== '-') {
            $metadataEmbedded = officialMetadata('npm', $embeddedName, $metadataDir, $online);
            $lockedEmbedded = npmLockMetadata($npmLock, $embeddedName);
            if ($metadataEmbedded['license'] === '-' && $lockedEmbedded['license'] !== '-') {
                $metadataEmbedded['license'] = $lockedEmbedded['license'];
            }
            if ($metadataEmbedded['status'] === 'unknown' && (isset($rootNpmDependencies[$embeddedName]) || isset($manifestVersions[$embeddedName]))) {
                $metadataEmbedded['status'] = 'managed';
            }
            addRow($rows, [
                'kind' => 'browser', 'name' => $embeddedName, 'relationship' => 'bundled',
                'current' => $embeddedVersion, 'latest' => $metadataEmbedded['latest'],
                'license' => $metadataEmbedded['license'], 'status' => $metadataEmbedded['status'],
                'source' => implode(';', $sourcePaths), 'call_sites' => callSites($sourceLines, $needles),
                'flags' => isset($browserPackages[$embeddedName]) ? 'duplicate-runtime-candidate' : '-',
                'metadata_url' => $metadataEmbedded['url'],
            ]);
            if (isset($browserPackages[$embeddedName])) {
                addFinding($rows, $findings, 'duplicate-browser-package', $browserPackages[$embeddedName]['source'].';'.implode(';', $sourcePaths), 'bundled and vendored copies', $embeddedName);
            }
            $inventoriedNpm[$embeddedName] = true;
        }
    }
}

// Root npm packages without a standalone asset directory remain part of the reproducible browser build.
foreach ($rootNpmDependencies as $packageName => $declaredVersion) {
    if ($packageName === '@adminkit/core' || isset($inventoriedNpm[$packageName])) {
        continue;
    }
    $lockedNpm = npmLockMetadata($npmLock, $packageName);
    $current = $lockedNpm['version'] !== '-' ? $lockedNpm['version'] : ltrim((string) $declaredVersion, '^~v');
    $metadata = officialMetadata('npm', $packageName, $metadataDir, $online);
    $license = $lockedNpm['license'] !== '-' ? $lockedNpm['license'] : $metadata['license'];
    $relationship = array_key_exists($packageName, $rootNpmDev) ? 'build-tool' : 'build-input';
    $rowFlags = [];
    if ($metadata['latest'] !== '-' && $current !== '-' && version_compare($current, $metadata['latest'], '<')) {
        $rowFlags[] = 'outdated';
    }
    if (($packageName === 'jquery' && isset($manifestHolds['jquery'])) || ($packageName === 'bootstrap' && isset($manifestHolds['publicBootstrap']))) {
        $rowFlags[] = 'compatibility-hold';
    }
    addRow($rows, [
        'kind' => 'browser', 'name' => $packageName, 'relationship' => $relationship,
        'current' => $current, 'latest' => $metadata['latest'], 'license' => $license,
        'status' => $metadata['status'] === 'unknown' ? 'managed' : $metadata['status'],
        'source' => 'package.json;package-lock.json', 'call_sites' => '-',
        'flags' => flags($rowFlags), 'metadata_url' => $metadata['url'],
    ]);
    $inventoriedNpm[$packageName] = true;
}

// Parse CDN definitions as text. Never include or execute application PHP.
$cdnPath = $root.'/app/config/cdn.php';
$cdnPackages = [
    'editor' => 'ckeditor4', 'simpleeditor' => 'ckeditor4', 'datetimepicker' => '@fengyuanchen/datepicker',
    'codeeditor' => 'ace-builds', 'spectrum' => 'spectrum-colorpicker',
    'autocomplete' => 'devbridge-autocomplete', 'daterangepicker' => 'daterangepicker',
    'hljs' => 'highlight.js', 'blockadblock' => 'blockadblock',
];
$lifecycle = [
    'ckeditor4' => ['eol', 'https://ckeditor.com/ckeditor-4-support/'],
    'moment' => ['maintenance', 'https://momentjs.com/docs/#/-project-status/'],
];
if (is_file($cdnPath)) {
    $contents = file_get_contents($cdnPath) ?: '';
    if (preg_match_all('/^ {4}[\'\"]([A-Za-z0-9_-]+)[\'\"]\s*=>\s*\[(.*?)(?=^ {4}[\'\"][A-Za-z0-9_-]+[\'\"]\s*=>\s*\[|^\];)/ms', $contents, $definitions, PREG_SET_ORDER)) {
        foreach ($definitions as $definition) {
            $key = $definition[1];
            $body = $definition[2];
            preg_match('/[\'\"]version[\'\"]\s*=>\s*[\'\"]([^\'\"]*)[\'\"]/', $body, $versionMatch);
            $version = trim((string) ($versionMatch[1] ?? '')) ?: '-';
            preg_match_all('/[\'\"]((?:https?:)?\/\/[^\'\"]+)[\'\"]/', $body, $urlMatches);
            $urls = $urlMatches[1] ?? [];
            sort($urls, SORT_STRING);
            preg_match_all('/assets\([\'\"]([^\'\"]+)[\'\"]\)/', $body, $assetMatches);
            $localAssets = $assetMatches[1] ?? [];
            sort($localAssets, SORT_STRING);
            $packageName = str_contains($body, 'vendor/jodit/') ? 'jodit' : ($cdnPackages[$key] ?? $key);
            if ($key === 'datetimepicker' && $localAssets !== []) {
                $packageName = '@chenfengyuan/datepicker';
            }
            if ($key === 'hljs' && $localAssets !== []) {
                $packageName = '@highlightjs/cdn-assets';
            }
            if (array_filter($urls, static fn (string $url): bool => str_contains($url, 'ckeditor.com')) !== []) {
                $packageName = 'ckeditor4';
            }
            $relationship = $localAssets !== [] && $urls === [] ? 'self-hosted' : 'remote';
            $metadata = officialMetadata('npm', $packageName, $metadataDir, $online);
            $status = $metadata['status'];
            $metadataUrl = $metadata['url'];
            if (isset($lifecycle[$packageName])) {
                [$status, $metadataUrl] = $lifecycle[$packageName];
            }
            $managedPackage = $browserPackages[$packageName] ?? null;
            $license = $metadata['license'];
            if ($license === '-' && is_array($managedPackage) && ($managedPackage['license'] ?? '-') !== '-') {
                $license = $managedPackage['license'];
            }
            if ($status === 'unknown' && $relationship === 'self-hosted' && is_array($managedPackage)) {
                $status = 'managed';
            }
            $rowFlags = [];
            if ($version === '-') {
                $rowFlags[] = 'unversioned';
            }
            foreach ($urls as $url) {
                if (!str_contains($url, '[version]') && !str_contains($url, $version)) {
                    $rowFlags[] = 'url-unpinned';
                }
            }
            if ($metadata['latest'] !== '-' && $version !== '-' && version_compare(ltrim($version, 'v'), ltrim($metadata['latest'], 'v'), '<')) {
                $rowFlags[] = 'outdated';
            }
            if (in_array($status, ['eol', 'maintenance'], true)) {
                $rowFlags[] = $status;
            }
            addRow($rows, [
                'kind' => 'cdn', 'name' => $key, 'relationship' => $relationship,
                'current' => $version, 'latest' => $metadata['latest'], 'license' => $license,
                'status' => $status, 'source' => 'app/config/cdn.php:'.implode(',', array_merge($localAssets, $urls)),
                'call_sites' => callSites($sourceLines, ["CDN::load('".$key."'", 'CDN::load("'.$key.'"']),
                'flags' => flags($rowFlags), 'metadata_url' => $metadataUrl,
            ]);
            if ($version === '-' || $packageName === $key && !array_key_exists($key, $cdnPackages)) {
                addFinding($rows, $findings, 'unknown-or-unversioned', 'app/config/cdn.php:'.$key, 'definition unresolved', $key);
            }

            foreach ($urls as $url) {
                if (str_contains($url, 'moment')) {
                    [$momentStatus, $momentUrl] = $lifecycle['moment'];
                    $momentMetadata = officialMetadata('npm', 'moment', $metadataDir, $online);
                    addRow($rows, [
                        'kind' => 'cdn', 'name' => 'moment', 'relationship' => 'remote-subdependency',
                        'current' => '-', 'latest' => $momentMetadata['latest'], 'license' => $momentMetadata['license'],
                        'status' => $momentStatus, 'source' => 'app/config/cdn.php:'.$url,
                        'call_sites' => callSites($sourceLines, ["CDN::load('".$key."'", 'CDN::load("'.$key.'"']),
                        'flags' => 'maintenance,unversioned,url-unpinned', 'metadata_url' => $momentUrl,
                    ]);
                    addFinding($rows, $findings, 'unknown-or-unversioned', 'app/config/cdn.php:'.$url, 'remote version floats', 'moment');
                }
            }
        }
    }
}

// Admin shell asset loads from admin and dashboard layouts.
$layoutFiles = [];
foreach ($sourceFiles as $file) {
    $relative = relativePath($root, $file);
    if (preg_match('#storage/themes/[^/]+/(?:admin/layouts|layouts)/(?:main|dashboard)\.php$#', $relative)) {
        $layoutFiles[] = $file;
    }
}
foreach ($layoutFiles as $layoutFile) {
    $contents = file_get_contents($layoutFile) ?: '';
    preg_match_all('#(?:assets\([\'\"]([^\'\"]+)[\'\"]\)|/static/([A-Za-z0-9_./-]+\.(?:js|css)))#', $contents, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $asset = $match[1] !== '' ? $match[1] : $match[2];
        $packageName = 'asset:'.preg_replace('/\.min(?=\.(?:js|css)$)/', '', basename($asset));
        foreach ($npmNames as $directory => $npmName) {
            if (str_contains($asset, 'frontend/libs/'.$directory.'/')) {
                $packageName = $npmName;
                break;
            }
        }
        if (str_contains(strtolower($asset), 'chart')) {
            $packageName = 'chart.js';
        }
        $known = $browserPackages[$packageName] ?? ['version' => '-', 'license' => '-', 'source' => 'public/static/'.$asset];
        $rowFlags = $known['version'] === '-' ? 'unversioned' : '-';
        addRow($rows, [
            'kind' => 'admin-shell', 'name' => $packageName, 'relationship' => 'loaded',
            'current' => $known['version'], 'license' => $known['license'],
            'status' => $known['version'] === '-' ? 'unresolved' : 'active',
            'source' => relativePath($root, $layoutFile).':'.$asset,
            'call_sites' => callSites($sourceLines, [$asset]), 'flags' => $rowFlags,
        ]);
    }
}

if (isset($manifestVersions['@adminkit/core'])) {
    $metadata = officialMetadata('npm', '@adminkit/core', $metadataDir, $online);
    $lockedNpm = npmLockMetadata($npmLock, '@adminkit/core');
    $current = (string) $manifestVersions['@adminkit/core'];
    $rowFlags = [];
    if ($metadata['latest'] !== '-' && version_compare($current, $metadata['latest'], '<')) {
        $rowFlags[] = 'outdated';
    }
    if (isset($manifestHolds['dashboardBundle'])) {
        $rowFlags[] = 'compatibility-hold';
    }
    $license = $metadata['license'] !== '-' ? $metadata['license'] : $lockedNpm['license'];
    $status = $metadata['status'] === 'unknown' ? 'managed' : $metadata['status'];
    addRow($rows, [
        'kind' => 'admin-shell', 'name' => '@adminkit/core', 'relationship' => 'bundled',
        'current' => $current, 'latest' => $metadata['latest'], 'license' => $license,
        'status' => $status, 'source' => 'public/static/vendor-manifest.json;public/static/backend/js/app.js;public/static/backend/css/app.css',
        'call_sites' => callSites($sourceLines, ['backend/js/app.js', 'backend/css/app.css']),
        'flags' => flags($rowFlags), 'metadata_url' => $metadata['url'],
    ]);
}

$adminNestedPrefix = 'node_modules/@adminkit/core/node_modules/';
foreach (is_array($npmLock['packages'] ?? null) ? $npmLock['packages'] : [] as $lockPath => $package) {
    if (!str_starts_with((string) $lockPath, $adminNestedPrefix) || !is_array($package)) {
        continue;
    }
    $packageName = substr((string) $lockPath, strlen($adminNestedPrefix));
    if ($packageName === '' || str_contains($packageName, '/node_modules/')) {
        continue;
    }
    $metadata = officialMetadata('npm', $packageName, $metadataDir, $online);
    $license = $package['license'] ?? $metadata['license'];
    if (is_array($license)) {
        $license = implode(',', $license);
    }
    addRow($rows, [
        'kind' => 'admin-shell', 'name' => $packageName, 'relationship' => 'embedded',
        'current' => (string) ($package['version'] ?? '-'), 'latest' => $metadata['latest'],
        'license' => (string) $license ?: '-',
        'status' => $metadata['status'] === 'unknown' ? 'managed' : $metadata['status'],
        'source' => 'package-lock.json;public/static/backend/js/app.js',
        'call_sites' => callSites($sourceLines, ['backend/js/app.js']),
        'flags' => isset($manifestHolds['dashboardBundle']) ? 'compatibility-hold,embedded-version' : 'embedded-version',
        'metadata_url' => $metadata['url'],
    ]);
}

// Installed plugins and theme/addon manifests.
foreach ([['storage/plugins', 'plugin', 'installed'], ['storage/addons', 'addon', 'installed'], ['storage/themes', 'addon', 'theme']] as [$directory, $kind, $relationship]) {
    $base = $root.'/'.$directory;
    if (!is_dir($base)) {
        continue;
    }
    $children = array_values(array_filter(scandir($base) ?: [], static fn (string $name): bool => $name !== '.' && $name !== '..'));
    sort($children, SORT_STRING);
    foreach ($children as $child) {
        if (!is_dir($base.'/'.$child)) {
            continue;
        }
        $manifestPath = $base.'/'.$child.'/config.json';
        $manifest = jsonFile($manifestPath);
        if ($manifest === null) {
            addFinding($rows, $findings, 'unknown-or-unversioned', relativePath($root, $base.'/'.$child), 'manifest missing', $kind.':'.$child);
            continue;
        }
        $version = trim((string) ($manifest['version'] ?? '')) ?: '-';
        $license = $manifest['license'] ?? '-';
        if (is_array($license)) {
            $license = implode(',', $license);
        }
        $rowFlags = [];
        if ($version === '-') {
            $rowFlags[] = 'unversioned';
        }
        if ($license === '-') {
            $rowFlags[] = 'license-unknown';
        }
        addRow($rows, [
            'kind' => $kind, 'name' => (string) ($manifest['id'] ?? $child),
            'relationship' => $relationship, 'current' => $version, 'license' => (string) $license,
            'status' => 'local', 'source' => relativePath($root, $manifestPath),
            'call_sites' => $kind === 'plugin' ? 'app/controllers/admin/PluginsController.php' : relativePath($root, $base.'/'.$child),
            'flags' => flags($rowFlags),
        ]);
        if ($version === '-') {
            addFinding($rows, $findings, 'unknown-or-unversioned', relativePath($root, $manifestPath), 'version unresolved', $kind.':'.$child);
        }
    }
}

$kindOrder = ['composer' => 10, 'browser' => 20, 'cdn' => 30, 'admin-shell' => 40, 'plugin' => 50, 'addon' => 60, 'finding' => 90];
usort($rows, static function (array $left, array $right) use ($kindOrder): int {
    return [
        $kindOrder[$left['kind']] ?? 80, $left['name'], $left['relationship'], $left['source'],
    ] <=> [
        $kindOrder[$right['kind']] ?? 80, $right['name'], $right['relationship'], $right['source'],
    ];
});

if ($format === 'tsv') {
    $columns = ['kind', 'name', 'relationship', 'current', 'constraint', 'latest', 'license', 'status', 'source', 'call_sites', 'flags'];
    echo implode("\t", $columns)."\n";
    foreach ($rows as $row) {
        echo implode("\t", array_map(static fn (string $column): string => $row[$column], $columns))."\n";
    }
} else {
    echo "# Dependency inventory\n\n";
    echo "Generated deterministically from repository files. Upstream values are populated only from supplied cache files or explicit `--online` queries.\n\n";
    echo "## Release table\n\n";
    echo "| Kind | Dependency | Relationship | Current | Constraint | Latest stable | License | Status | Evidence | Call sites | Flags |\n";
    echo "| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |\n";
    foreach ($rows as $row) {
        if ($row['kind'] === 'finding') {
            continue;
        }
        $evidence = $row['source'];
        if ($row['metadata_url'] !== '-') {
            $evidence .= ';'.$row['metadata_url'];
        }
        $values = [$row['kind'], $row['name'], $row['relationship'], $row['current'], $row['constraint'], $row['latest'], $row['license'], $row['status'], $evidence, $row['call_sites'], $row['flags']];
        $values = array_map(static fn (string $value): string => str_replace('|', '\\|', $value), $values);
        echo '| '.implode(' | ', $values)." |\n";
    }
    echo "\n## Findings\n\n";
    if ($findings === 0) {
        echo "- None.\n";
    } else {
        foreach ($rows as $row) {
            if ($row['kind'] === 'finding') {
                echo '- `'.$row['name'].'`: '.$row['status'].' in `'.$row['source'].'` ('.$row['flags'].").\n";
            }
        }
    }
}

exit($failOnFindings && $findings > 0 ? 2 : 0);
PHP
