#!/bin/sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$ROOT"

scan() {
    label=$1
    pattern=$2
    shift 2
    echo "$label"
    rg -n --glob '*.php' --glob '!vendor/**' "$pattern" "$@" || true
}

scan "Dangerous parsing and execution" 'unserialize\(|eval\(|shell_exec\(|exec\(|system\(|passthru\(' .
scan "Legacy hashes and deprecated sanitizers" 'md5\(|sha1\(|FILTER_SANITIZE_STRING' app core
scan "State-changing routes that may still use GET" "Gem::(get|route)\([^\n]*(delete|cancel|reset|toggle|archive|activate|approve|ban|restore|mark)" app/routes.php
scan "Outbound request call sites" 'Http::url\(|file_get_contents\([^\n]*\$' app core
scan "Client-supplied upload MIME checks" "\['type'\]|mimematch\(" app core
scan "Legacy password compatibility" 'md5\([^\n]*password|strlen\([^\n]*password[^\n]*32' app core

echo "Tracked secret and runtime path candidates"
git ls-files | rg '(^|/)(config\.php|\.env($|\.)|.*\.(key|pem|p12|pfx|sql|log|gem)$|storage/(cache|logs)/|public/content/)' || true

echo "Conflict markers"
rg -n --glob '!vendor/**' '^(<<<<<<<|=======|>>>>>>>)' . || true
