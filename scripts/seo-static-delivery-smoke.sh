#!/usr/bin/env bash
set -uo pipefail

BASE_URL="${BASE_URL:-https://prawkonaraz.pl}"
STRICT="${STRICT:-1}"
CHECK_NEWSROOM_FEED="${CHECK_NEWSROOM_FEED:-0}"
CURL_TIMEOUT_SECONDS="${CURL_TIMEOUT_SECONDS:-20}"

BASE_URL="${BASE_URL%/}"
FAILURES=0
TMP_DIR="$(mktemp -d)"

cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

fail() {
    printf 'FAIL: %s\n' "$*" >&2
    FAILURES=$((FAILURES + 1))
}

pass() {
    printf 'PASS: %s\n' "$*"
}

header_value() {
    local headers_file="$1"
    local header_name="$2"

    awk -v name="$header_name" '
        BEGIN { IGNORECASE = 1 }
        $0 ~ "^" name ":" {
            sub(/^[^:]+:[[:space:]]*/, "")
            sub(/\r$/, "")
            value = $0
        }
        END { print value }
    ' "$headers_file"
}

has_header() {
    local headers_file="$1"
    local header_name="$2"

    grep -Eiq "^$header_name:" "$headers_file"
}

request() {
    local url="$1"
    local headers_file="$2"
    local body_file="$3"
    shift 3

    curl         --silent         --show-error         --max-time "$CURL_TIMEOUT_SECONDS"         --dump-header "$headers_file"         --output "$body_file"         --write-out '%{http_code}'         "$@"         "$url"
}

check_public_cache_control() {
    local label="$1"
    local cache_control="$2"

    if [[ "$cache_control" != *"public"* || "$cache_control" != *"max-age="* ]]; then
        fail "$label Cache-Control is not explicitly public with max-age: '${cache_control:-<missing>}'"
        return
    fi

    pass "$label Cache-Control: $cache_control"
}

check_conditional_304() {
    local label="$1"
    local url="$2"
    local etag="$3"
    local last_modified="$4"
    local headers_file="$TMP_DIR/${label//[^A-Za-z0-9]/_}-conditional.headers"
    local body_file="$TMP_DIR/${label//[^A-Za-z0-9]/_}-conditional.body"
    local status=""

    if [[ -n "$etag" ]]; then
        status="$(request "$url" "$headers_file" "$body_file" --header "If-None-Match: $etag")" || {
            fail "$label conditional ETag request failed"
            return
        }
    elif [[ -n "$last_modified" ]]; then
        status="$(request "$url" "$headers_file" "$body_file" --header "If-Modified-Since: $last_modified")" || {
            fail "$label conditional Last-Modified request failed"
            return
        }
    else
        fail "$label exposes neither ETag nor Last-Modified"
        return
    fi

    if [[ "$status" != "304" ]]; then
        fail "$label conditional request returned HTTP $status instead of 304"
        return
    fi

    pass "$label conditional request returns 304"
}

check_endpoint() {
    local label="$1"
    local path="$2"
    local expected_content_type_prefix="$3"
    local url="$BASE_URL$path"
    local key="${label//[^A-Za-z0-9]/_}"
    local headers_file="$TMP_DIR/$key.headers"
    local body_file="$TMP_DIR/$key.body"
    local status=""

    printf '\n=== %s %s ===\n' "$label" "$url"

    status="$(request "$url" "$headers_file" "$body_file")" || {
        fail "$label request failed"
        return
    }

    cat "$headers_file"

    if [[ "$status" != "200" ]]; then
        fail "$label returned HTTP $status instead of 200"
        return
    fi
    pass "$label HTTP 200"

    local content_type cache_control etag last_modified
    content_type="$(header_value "$headers_file" "Content-Type")"
    cache_control="$(header_value "$headers_file" "Cache-Control")"
    etag="$(header_value "$headers_file" "ETag")"
    last_modified="$(header_value "$headers_file" "Last-Modified")"

    if [[ "$content_type" != "$expected_content_type_prefix"* ]]; then
        fail "$label Content-Type '$content_type' does not start with '$expected_content_type_prefix'"
    else
        pass "$label Content-Type: $content_type"
    fi

    check_public_cache_control "$label" "$cache_control"

    if has_header "$headers_file" "Set-Cookie"; then
        fail "$label unexpectedly returns Set-Cookie"
    else
        pass "$label has no Set-Cookie"
    fi

    if [[ -n "$etag" || -n "$last_modified" ]]; then
        pass "$label validators present (ETag/Last-Modified)"
    else
        fail "$label exposes no validator"
    fi

    check_conditional_304 "$label" "$url" "$etag" "$last_modified"
}

check_endpoint "robots" "/robots.txt" "text/plain"
check_endpoint "sitemap-root" "/sitemap.xml" "application/xml"
check_endpoint "sitemap-static" "/sitemaps/static.xml" "application/xml"

if [[ "$CHECK_NEWSROOM_FEED" == "1" ]]; then
    check_endpoint "newsroom-feed" "/aktualnosci/feed.xml" "application/atom+xml"
fi

printf '\nProduction SEO delivery smoke failures: %d\n' "$FAILURES"

if (( FAILURES > 0 )); then
    if [[ "$STRICT" == "1" ]]; then
        printf 'STRICT mode: failing smoke.\n' >&2
        exit 1
    fi

    printf 'REPORT-ONLY mode: failures are evidence to fix/deploy, not a passing production verification.\n'
    exit 0
fi

printf 'All requested production SEO delivery checks passed.\n'
