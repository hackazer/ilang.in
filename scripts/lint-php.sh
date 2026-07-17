#!/bin/sh
set -eu

export LC_ALL=C

file_list=$(mktemp "${TMPDIR:-/tmp}/ilang-php-lint.XXXXXX")
trap 'rm -f "$file_list"' EXIT HUP INT TERM

git ls-files '*.php' | while IFS= read -r file; do
    case "$file" in
        vendor/*|storage/plugins/*|storage/addons/*|storage/backups/*|storage/cache/*|storage/logs/*|storage/sessions/*|storage/temp/*|storage/tmp/*|storage/uploads/*)
            continue
            ;;
    esac
    printf '%s\n' "$file"
done | sort -u > "$file_list"

status=0

while IFS= read -r file; do
    if [ ! -f "$file" ]; then
        # Deleted tracked assets remain in git ls-files until the migration is
        # committed. They are not PHP sources that can be linted.
        continue
    fi

    if ! output=$(php \
        -d error_reporting=E_ALL \
        -d display_errors=1 \
        -d display_startup_errors=1 \
        -d log_errors=0 \
        -l "$file" 2>&1); then
        printf '%s\n' "$output" >&2
        status=1
        continue
    fi

    printf '%s\n' "$output"

    if printf '%s\n' "$output" | grep -Eq '(^|[[:space:]])(PHP )?Deprecated:'; then
        status=1
    fi
done < "$file_list"

deprecated_curl_calls=$(grep -R -n --include='*.php' 'curl_close[[:space:]]*(' app core || true)

if [ -n "$deprecated_curl_calls" ]; then
    printf '%s\n' "$deprecated_curl_calls" >&2
    status=1
fi

exit "$status"
