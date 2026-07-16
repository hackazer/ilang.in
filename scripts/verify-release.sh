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
fi

echo "Running source and dependency inventories"
sh scripts/audit-source.sh > /tmp/ilang-source-audit.txt
if [ -x scripts/dependency-inventory.sh ]; then
    sh scripts/dependency-inventory.sh > /tmp/ilang-dependency-inventory.txt
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
