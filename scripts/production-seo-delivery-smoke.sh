#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-https://prawkonaraz.pl}"
REQUIRE_NEWSROOM_FEED="${REQUIRE_NEWSROOM_FEED:-0}"
EXPECT_NEWSROOM_PUBLIC="${EXPECT_NEWSROOM_PUBLIC:-auto}"
TMP_DIR="$(mktemp -d)"

if [[ "$EXPECT_NEWSROOM_PUBLIC" != "auto" && "$EXPECT_NEWSROOM_PUBLIC" != "0" && "$EXPECT_NEWSROOM_PUBLIC" != "1" ]]; then
    echo "EXPECT_NEWSROOM_PUBLIC must be auto, 0 or 1." >&2
    exit 1
fi

if [[ "$EXPECT_NEWSROOM_PUBLIC" == "0" && "$REQUIRE_NEWSROOM_FEED" == "1" ]]; then
    echo "EXPECT_NEWSROOM_PUBLIC=0 conflicts with REQUIRE_NEWSROOM_FEED=1." >&2
    exit 1
fi

cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

header_value() {
    local headers="$1"
    local name="$2"

    awk -v target="${name,,}" '
        BEGIN { IGNORECASE = 1 }
        {
            line=$0
            sub(/\r$/, "", line)
            split(line, parts, ":")
            if (tolower(parts[1]) == target) {
                sub(/^[^:]+:[[:space:]]*/, "", line)
                value=line
            }
        }
        END { print value }
    ' "$headers"
}

fetch() {
    local name="$1"
    local path="$2"
    local headers="$TMP_DIR/${name}.headers"
    local body="$TMP_DIR/${name}.body"

    curl --fail-with-body --silent --show-error --compressed \
        --dump-header "$headers" \
        --output "$body" \
        "$BASE_URL$path"

    local status
    status="$(awk 'toupper($1) ~ /^HTTP\// {code=$2} END {print code}' "$headers")"

    printf '%s|%s|%s\n' "$status" "$headers" "$body"
}

assert_no_set_cookie() {
    local headers="$1"

    if grep -qi '^Set-Cookie:' "$headers"; then
        echo "Unexpected Set-Cookie header in static crawler response." >&2
        return 1
    fi
}

assert_public_cache() {
    local headers="$1"
    local cache_control

    cache_control="$(header_value "$headers" "Cache-Control")"

    if [[ ! "$cache_control" =~ public ]] || [[ ! "$cache_control" =~ max-age=3600 ]]; then
        echo "Expected Cache-Control: public, max-age=3600; got: $cache_control" >&2
        return 1
    fi
}

assert_content_type() {
    local headers="$1"
    local expected="$2"
    local content_type

    content_type="$(header_value "$headers" "Content-Type")"

    if [[ "$content_type" != *"$expected"* ]]; then
        echo "Expected Content-Type containing $expected; got: $content_type" >&2
        return 1
    fi
}

assert_conditional_304() {
    local path="$1"
    local headers="$2"
    local etag
    local last_modified
    local status

    etag="$(header_value "$headers" "ETag")"
    last_modified="$(header_value "$headers" "Last-Modified")"

    if [[ -n "$etag" ]]; then
        status="$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' \
            --header "If-None-Match: $etag" "$BASE_URL$path")"
    elif [[ -n "$last_modified" ]]; then
        status="$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' \
            --header "If-Modified-Since: $last_modified" "$BASE_URL$path")"
    else
        echo "Expected ETag or Last-Modified for $path." >&2
        return 1
    fi

    if [[ "$status" != "304" ]]; then
        echo "Expected conditional 304 for $path; got HTTP $status." >&2
        return 1
    fi
}

check_static_resource() {
    local name="$1"
    local path="$2"
    local expected_type="$3"
    local result
    local status
    local headers
    local body

    result="$(fetch "$name" "$path")"
    IFS='|' read -r status headers body <<< "$result"

    if [[ "$status" != "200" ]]; then
        echo "Expected HTTP 200 for $path; got $status." >&2
        return 1
    fi

    assert_content_type "$headers" "$expected_type"
    assert_public_cache "$headers"
    assert_no_set_cookie "$headers"
    assert_conditional_304 "$path" "$headers"

    if [[ "$path" == "/robots.txt" ]] && ! grep -Fq 'Sitemap: https://prawkonaraz.pl/sitemap.xml' "$body"; then
        echo "robots.txt is missing the canonical Sitemap directive." >&2
        return 1
    fi

    echo "PASS $path"
}

check_feed() {
    local headers="$TMP_DIR/feed.headers"
    local body="$TMP_DIR/feed.body"
    local status

    curl --silent --show-error --compressed \
        --dump-header "$headers" \
        --output "$body" \
        "$BASE_URL/aktualnosci/feed.xml"

    status="$(awk 'toupper($1) ~ /^HTTP\// {code=$2} END {print code}' "$headers")"

    if [[ "$status" == "404" ]]; then
        if [[ "$REQUIRE_NEWSROOM_FEED" == "1" || "$EXPECT_NEWSROOM_PUBLIC" == "1" ]]; then
            echo "Expected newsroom public gate enabled, but feed returned HTTP 404." >&2
            return 1
        fi

        echo "PASS /aktualnosci/feed.xml (404 confirms newsroom public gate is disabled)"
        return 0
    fi

    if [[ "$status" != "200" ]]; then
        echo "Expected HTTP 200 for feed; got $status." >&2
        return 1
    fi

    if [[ "$EXPECT_NEWSROOM_PUBLIC" == "0" ]]; then
        echo "Expected newsroom public gate disabled, but feed returned HTTP 200." >&2
        return 1
    fi

    assert_content_type "$headers" "application/atom+xml"
    assert_no_set_cookie "$headers"
    assert_conditional_304 "/aktualnosci/feed.xml" "$headers"

    echo "PASS /aktualnosci/feed.xml"
}

check_static_resource "robots" "/robots.txt" "text/plain"
check_static_resource "sitemap" "/sitemap.xml" "application/xml"
check_static_resource "static-sitemap" "/sitemaps/static.xml" "application/xml"
check_feed

echo "Production SEO delivery smoke passed."
