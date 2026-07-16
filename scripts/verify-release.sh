#!/bin/sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$ROOT"

if [ -z "${COMPOSER_BIN:-}" ] && [ -f /tmp/composer-2.10.2.phar ]; then
    COMPOSER_BIN=/tmp/composer-2.10.2.phar
else
    COMPOSER_BIN=${COMPOSER_BIN:-composer}
fi

composer_run() {
    case "$COMPOSER_BIN" in
        *.phar) php "$COMPOSER_BIN" "$@" ;;
        *) "$COMPOSER_BIN" "$@" ;;
    esac
}

COMPOSER_VERSION=$(composer_run --version | sed -n 's/^Composer version \([0-9][0-9.]*\).*/\1/p')
case "$COMPOSER_VERSION" in
    2.8.*|2.9.*|2.1[0-9].*|2.[2-9][0-9].*) ;;
    *)
        echo "Composer 2.8 or newer is required, found ${COMPOSER_VERSION:-unknown}." >&2
        exit 1
        ;;
esac

echo "Verifying Composer metadata"
composer_run validate --strict --no-interaction
composer_run install --prefer-dist --no-progress --no-interaction
composer_run audit --locked --abandoned=fail --no-interaction
composer_run check-platform-reqs --no-interaction

echo "Running PHP behavior and syntax checks"
vendor/bin/phpunit
sh scripts/lint-php.sh

if [ -f package-lock.json ]; then
    echo "Verifying locked browser dependencies"
    npm ci --ignore-scripts --no-audit --no-fund
    npm audit --audit-level=high
    npm test
    npm run check:browser
fi

echo "Running source and dependency inventories"
sh scripts/audit-source.sh > /tmp/ilang-source-audit.txt
if [ -x scripts/dependency-inventory.sh ]; then
    sh scripts/dependency-inventory.sh > /tmp/ilang-dependency-inventory.txt
    php -- /tmp/ilang-dependency-inventory.txt package.json <<'PHP'
<?php

declare(strict_types=1);

[$program, $inventoryPath, $packagePath] = $argv;
$package = json_decode((string) file_get_contents($packagePath), true, 512, JSON_THROW_ON_ERROR);
$policy = $package['dependencyReleasePolicy'] ?? null;
if (!is_array($policy)) {
    fwrite(STDERR, "Missing dependencyReleasePolicy in package.json.\n");
    exit(1);
}

$managedAssets = array_fill_keys($policy['managedFirstPartyAssets'] ?? [], true);
$parallelRepresentations = array_fill_keys($policy['allowedParallelRepresentations'] ?? [], true);
$duplicatePackages = array_fill_keys($policy['allowedDuplicatePackages'] ?? [], true);
$compatibilityHolds = $policy['compatibilityHolds'] ?? [];
$violations = [];
$handle = fopen($inventoryPath, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Cannot read dependency inventory.\n");
    exit(1);
}

$header = fgetcsv($handle, separator: "\t", escape: '');
if (!is_array($header)) {
    fwrite(STDERR, "Dependency inventory is empty.\n");
    exit(1);
}

while (($values = fgetcsv($handle, separator: "\t", escape: '')) !== false) {
    if (count($values) !== count($header)) {
        $violations[] = 'malformed inventory row';
        continue;
    }
    $row = array_combine($header, $values);
    if (!is_array($row)) {
        $violations[] = 'malformed inventory columns';
        continue;
    }

    $flags = $row['flags'] === '-' ? [] : explode(',', $row['flags']);
    $dependencyKey = $row['kind'].':'.$row['name'];
    if (in_array('compatibility-hold', $flags, true) && !isset($compatibilityHolds[$dependencyKey])) {
        $violations[] = 'undocumented compatibility hold '.$dependencyKey;
    }

    $unsupported = in_array($row['status'], ['eol', 'discontinued'], true)
        || str_starts_with($row['status'], 'abandoned:')
        || str_starts_with($row['status'], 'deprecated:');
    if ($unsupported && !isset($compatibilityHolds[$dependencyKey])) {
        $violations[] = 'unsupported dependency '.$dependencyKey.' ('.$row['status'].')';
    }

    if ($row['kind'] !== 'finding') {
        continue;
    }

    $target = $row['flags'];
    $approved = match ($row['name']) {
        'unknown-or-unversioned' => isset($managedAssets[$target]),
        'parallel-browser-artifacts' => isset($parallelRepresentations[$target]),
        'duplicate-browser-package' => isset($duplicatePackages[$target]),
        default => false,
    };
    if (!$approved) {
        $violations[] = $row['name'].' for '.$target.' at '.$row['source'];
    }
}
fclose($handle);

if ($violations !== []) {
    fwrite(STDERR, "Unapproved dependency inventory findings:\n - ".implode("\n - ", array_values(array_unique($violations)))."\n");
    exit(1);
}

fwrite(STDOUT, "Dependency inventory matches the explicit release policy.\n");
PHP
fi

echo "Checking tracked secret and runtime files"
if git ls-files | rg -q '(^|/)(config\.php|\.env($|\.)|.*\.(key|pem|p12|pfx|sql|log|gem)$)'; then
    git ls-files | rg '(^|/)(config\.php|\.env($|\.)|.*\.(key|pem|p12|pfx|sql|log|gem)$)'
    exit 1
fi

echo "Checking conflict markers and patch whitespace"
if rg -n --glob '!vendor/**' --glob '!node_modules/**' '^(<<<<<<<|=======|>>>>>>>)' .; then
    exit 1
fi
git diff --check

echo "Release verification passed"
