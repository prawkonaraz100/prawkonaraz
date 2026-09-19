import fs from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const baseUrl = new URL(process.env.BASE_URL ?? 'https://prawkonaraz.pl');
const requireNewsroomPublic = process.env.REQUIRE_NEWSROOM_PUBLIC === '1';
const sampleUrls = {
    article: normalizedOptionalUrl(process.env.ARTICLE_URL),
    category: normalizedOptionalUrl(process.env.CATEGORY_URL),
    topic: normalizedOptionalUrl(process.env.TOPIC_URL),
};
const outputDir = path.join(process.cwd(), 'output', 'newsroom-enterprise-seo-production-validation');
const reportPath = path.join(outputDir, 'report.json');
const origin = baseUrl.origin;
const websiteId = `${origin}/#website`;
const organizationId = `${origin}/#organization`;

const report = {
    started_at: new Date().toISOString(),
    base_url: origin,
    require_newsroom_public: requireNewsroomPublic,
    samples: sampleUrls,
    checks: [],
    observations: {},
};

try {
    await fs.mkdir(outputDir, { recursive: true });

    await validateHomepage();
    const newsroom = await validateNewsroomHub();
    const sitemap = await validateSitemapIndex();
    await validateFeed(newsroom);
    await validateSamples();

    report.observations.sitemap_children = sitemap.childUrls;
    report.finished_at = new Date().toISOString();
    report.failures = report.checks.filter((check) => check.status === 'fail').length;
    report.warnings = report.checks.filter((check) => check.status === 'warn').length;
    report.status = report.failures === 0 ? 'ok' : 'failed';

    console.log(
        `[n6-003] ${report.status.toUpperCase()} checks=${report.checks.length} failures=${report.failures} warnings=${report.warnings}`,
    );

    if (report.failures > 0) {
        process.exitCode = 1;
    }
} catch (error) {
    addCheck('runtime', 'fail', error instanceof Error ? error.message : String(error));
    report.finished_at = new Date().toISOString();
    report.failures = report.checks.filter((check) => check.status === 'fail').length;
    report.warnings = report.checks.filter((check) => check.status === 'warn').length;
    report.status = 'failed';
    process.exitCode = 1;
} finally {
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
}

async function validateHomepage() {
    const url = new URL('/', origin).toString();
    const response = await fetchDocument(url);
    const html = response.body;
    const canonical = extractCanonical(html);
    const jsonLd = extractJsonLd(html);
    const nodes = flattenJsonLd(jsonLd);
    const website = nodes.find((node) => hasType(node, 'WebSite'));
    const organization = nodes.find((node) => hasType(node, 'Organization'));

    expect(response.status === 200, 'homepage.http-200', `Expected HTTP 200, got ${response.status}.`);
    expect(urlEquals(canonical, origin), 'homepage.self-canonical', `Expected self canonical ${origin}; got ${canonical ?? 'missing'}.`);
    expect(Boolean(website), 'homepage.website-node', 'Expected one WebSite node in homepage JSON-LD.');
    expect(Boolean(organization), 'homepage.organization-node', 'Expected one Organization node in homepage JSON-LD.');

    if (website) {
        expect(
            String(website['@id'] ?? '') === websiteId,
            'homepage.website-id',
            `Expected WebSite @id ${websiteId}; got ${String(website['@id'] ?? 'missing')}.`,
        );
        expect(
            urlEquals(readUrlValue(website.url), origin),
            'homepage.website-url',
            `Expected WebSite url ${origin}; got ${String(readUrlValue(website.url) ?? 'missing')}.`,
        );

        const siteName = String(website.name ?? '').trim();
        expect(siteName.length > 0, 'homepage.site-name', 'Expected non-empty WebSite name.');
        expect(!/orły na drodze/i.test(siteName), 'homepage.no-legacy-site-name', `Unexpected legacy site name: ${siteName}.`);
        report.observations.site_name = siteName;
    }

    if (organization) {
        expect(
            String(organization['@id'] ?? '') === organizationId,
            'homepage.organization-id',
            `Expected Organization @id ${organizationId}; got ${String(organization['@id'] ?? 'missing')}.`,
        );

        const logo = resolveLogo(organization.logo, nodes);
        expect(Boolean(logo?.url), 'homepage.organization-logo-url', 'Expected crawlable Organization logo URL.');

        if (logo?.url) {
            const meta = await fetchImageMeta(logo.url);
            expect(meta.status === 200, 'homepage.organization-logo-http', `Expected logo HTTP 200, got ${meta.status}.`);
            expect(
                meta.contentType.startsWith('image/'),
                'homepage.organization-logo-content-type',
                `Expected image/* logo content type, got ${meta.contentType || 'missing'}.`,
            );

            const width = positiveNumber(logo.width) ?? meta.width;
            const height = positiveNumber(logo.height) ?? meta.height;

            expect(
                Number.isFinite(width) && Number.isFinite(height),
                'homepage.organization-logo-dimensions-known',
                `Unable to determine logo dimensions for ${logo.url}.`,
            );

            if (Number.isFinite(width) && Number.isFinite(height)) {
                expect(
                    width >= 112 && height >= 112,
                    'homepage.organization-logo-minimum',
                    `Expected logo at least 112x112; got ${width}x${height}.`,
                );
                report.observations.organization_logo = {
                    url: logo.url,
                    width,
                    height,
                    content_type: meta.contentType,
                };
            }
        }
    }
}

async function validateNewsroomHub() {
    const url = new URL('/aktualnosci', origin).toString();
    const response = await fetchDocument(url);
    const canonical = extractCanonical(response.body);
    const metaRobots = extractMetaRobots(response.body);
    const headerRobots = response.headers.get('x-robots-tag') ?? '';
    const h1 = extractFirstTagText(response.body, 'h1');
    const description = extractMetaDescription(response.body);
    const nodes = flattenJsonLd(extractJsonLd(response.body));
    const collectionNode = nodes.find((node) => hasType(node, 'CollectionPage') || hasType(node, 'WebPage'));
    const hasNoindex = /\bnoindex\b/i.test(metaRobots) || /\bnoindex\b/i.test(headerRobots);

    expect(response.status === 200, 'newsroom.http-200', `Expected /aktualnosci HTTP 200, got ${response.status}.`);

    if (requireNewsroomPublic) {
        expect(urlEquals(canonical, url), 'newsroom.self-canonical', `Expected newsroom self canonical; got ${canonical ?? 'missing'}.`);
        expect(Boolean(h1), 'newsroom.h1', 'Expected newsroom H1.');
        expect(Boolean(description), 'newsroom.meta-description', 'Expected newsroom meta description.');
        expect(!hasNoindex, 'newsroom.indexable', `Expected indexable newsroom surface; robots=${metaRobots || headerRobots || 'none'}.`);
        expect(Boolean(collectionNode), 'newsroom.structured-data', 'Expected CollectionPage/WebPage JSON-LD on public newsroom hub.');
    } else {
        const looksLikePublicSurface = Boolean(canonical && h1 && description && collectionNode);

        if (!looksLikePublicSurface) {
            expect(
                hasNoindex,
                'newsroom.placeholder-noindex',
                'Non-public/placeholder newsroom surface must be noindex via meta robots or X-Robots-Tag.',
            );
        } else {
            addCheck('newsroom.public-surface-detected', 'pass', 'A crawlable newsroom surface is already live even though strict public requirement is disabled.');
        }
    }

    report.observations.newsroom = {
        status: response.status,
        canonical,
        h1,
        meta_description: description,
        meta_robots: metaRobots,
        x_robots_tag: headerRobots,
        structured_data_types: unique(nodes.flatMap((node) => normalizeTypes(node['@type']))),
    };

    return {
        response,
        canonical,
        h1,
        description,
        nodes,
        hasNoindex,
    };
}

async function validateSitemapIndex() {
    const url = new URL('/sitemap.xml', origin).toString();
    const response = await fetchDocument(url);

    expect(response.status === 200, 'sitemap-index.http-200', `Expected sitemap index HTTP 200, got ${response.status}.`);
    expect(
        (response.headers.get('content-type') ?? '').includes('xml'),
        'sitemap-index.content-type',
        `Expected XML content type, got ${response.headers.get('content-type') ?? 'missing'}.`,
    );
    expect(!response.headers.has('set-cookie'), 'sitemap-index.no-set-cookie', 'Static sitemap index must not set cookies.');

    const childUrls = extractXmlLocs(response.body);
    expect(childUrls.length > 0, 'sitemap-index.children', 'Expected at least one child sitemap in sitemap index.');
    expect(
        childUrls.every((childUrl) => belongsToOrigin(childUrl)),
        'sitemap-index.canonical-origin',
        'Every child sitemap URL must belong to the canonical production origin.',
    );

    const childResults = [];

    for (const childUrl of childUrls.slice(0, 30)) {
        const child = await fetchDocument(childUrl);
        childResults.push({
            url: childUrl,
            status: child.status,
            content_type: child.headers.get('content-type') ?? '',
            set_cookie: child.headers.has('set-cookie'),
        });

        expect(child.status === 200, `sitemap-child.http-200:${pathnameLabel(childUrl)}`, `Expected HTTP 200 for ${childUrl}; got ${child.status}.`);
        expect(
            (child.headers.get('content-type') ?? '').includes('xml'),
            `sitemap-child.content-type:${pathnameLabel(childUrl)}`,
            `Expected XML content type for ${childUrl}; got ${child.headers.get('content-type') ?? 'missing'}.`,
        );
        expect(!child.headers.has('set-cookie'), `sitemap-child.no-set-cookie:${pathnameLabel(childUrl)}`, `Unexpected Set-Cookie on ${childUrl}.`);
    }

    const articleSitemaps = childUrls.filter((childUrl) => /\/sitemaps\/articles(?:-\d+-\d+)?\.xml(?:$|\?)/.test(childUrl));
    const newsSitemaps = childUrls.filter((childUrl) => /\/sitemaps\/news(?:-\d+-\d+)?\.xml(?:$|\?)/.test(childUrl));

    if (requireNewsroomPublic) {
        expect(articleSitemaps.length > 0, 'sitemap-index.newsroom-articles-present', 'Expected article sitemap coverage while newsroom is public.');
        expect(newsSitemaps.length > 0, 'sitemap-index.news-present', 'Expected Google News sitemap coverage while newsroom is public.');
    } else {
        addCheck(
            'sitemap-index.newsroom-coverage',
            'warn',
            `Current sitemap index contains article_sitemaps=${articleSitemaps.length}, news_sitemaps=${newsSitemaps.length}; strict newsroom publication is not required in this run.`,
        );
    }

    report.observations.sitemap_children_detail = childResults;

    return {
        childUrls,
        articleSitemaps,
        newsSitemaps,
    };
}

async function validateFeed(newsroom) {
    const url = new URL('/aktualnosci/feed.xml', origin).toString();
    const response = await fetchDocument(url);

    if (response.status === 404 && !requireNewsroomPublic) {
        addCheck('feed.rollout-gated-404', 'pass', 'Feed 404 is accepted while newsroom public rollout is not required.');
        return;
    }

    expect(response.status === 200, 'feed.http-200', `Expected Atom feed HTTP 200, got ${response.status}.`);
    expect(
        (response.headers.get('content-type') ?? '').includes('application/atom+xml'),
        'feed.content-type',
        `Expected application/atom+xml, got ${response.headers.get('content-type') ?? 'missing'}.`,
    );
    expect(!response.headers.has('set-cookie'), 'feed.no-set-cookie', 'Atom feed must not set cookies.');

    const discoveryHref = extractAtomDiscoveryHref(newsroom.response.body);

    if (requireNewsroomPublic) {
        expect(
            urlEquals(discoveryHref, url),
            'feed.head-discovery',
            `Expected Atom discovery link ${url}; got ${discoveryHref ?? 'missing'}.`,
        );
    } else if (!discoveryHref) {
        addCheck('feed.head-discovery', 'warn', 'Feed is live but /aktualnosci does not currently expose an Atom discovery link.');
    }

    report.observations.feed = {
        status: response.status,
        content_type: response.headers.get('content-type') ?? '',
        discovery_href: discoveryHref,
    };
}

async function validateSamples() {
    for (const [kind, url] of Object.entries(sampleUrls)) {
        if (!url) {
            addCheck(
                `sample.${kind}.not-provided`,
                requireNewsroomPublic ? 'warn' : 'pass',
                `No ${kind} sample URL supplied for this run.`,
            );
            continue;
        }

        await validateSample(kind, url);
    }
}

async function validateSample(kind, url) {
    const response = await fetchDocument(url);
    const canonical = extractCanonical(response.body);
    const metaRobots = extractMetaRobots(response.body);
    const headerRobots = response.headers.get('x-robots-tag') ?? '';
    const nodes = flattenJsonLd(extractJsonLd(response.body));
    const h1 = extractFirstTagText(response.body, 'h1');

    expect(response.status === 200, `sample.${kind}.http-200`, `Expected ${kind} sample HTTP 200, got ${response.status}.`);
    expect(urlEquals(canonical, url), `sample.${kind}.self-canonical`, `Expected self canonical for ${url}; got ${canonical ?? 'missing'}.`);
    expect(Boolean(h1), `sample.${kind}.h1`, `Expected H1 for ${kind} sample.`);
    expect(
        !/\bnoindex\b/i.test(metaRobots) && !/\bnoindex\b/i.test(headerRobots),
        `sample.${kind}.indexable`,
        `Expected indexable ${kind} sample.`,
    );

    const website = nodes.find((node) => hasType(node, 'WebSite'));
    const organization = nodes.find((node) => hasType(node, 'Organization'));

    expect(String(website?.['@id'] ?? '') === websiteId, `sample.${kind}.website-id`, `Expected WebSite @id ${websiteId}.`);
    expect(String(organization?.['@id'] ?? '') === organizationId, `sample.${kind}.organization-id`, `Expected Organization @id ${organizationId}.`);

    if (kind === 'article') {
        const article = nodes.find((node) => hasType(node, 'NewsArticle') || hasType(node, 'Article'));
        const webPage = nodes.find((node) => hasType(node, 'WebPage'));

        expect(Boolean(article), 'sample.article.article-node', 'Expected NewsArticle/Article JSON-LD.');
        expect(Boolean(webPage), 'sample.article.webpage-node', 'Expected WebPage JSON-LD.');

        if (article) {
            expect(
                referenceId(article.publisher) === organizationId,
                'sample.article.publisher-id',
                `Expected article publisher @id ${organizationId}; got ${referenceId(article.publisher) ?? 'missing'}.`,
            );
            expect(
                referenceId(article.isPartOf) === websiteId,
                'sample.article.website-reference',
                `Expected article isPartOf @id ${websiteId}; got ${referenceId(article.isPartOf) ?? 'missing'}.`,
            );

            const published = String(article.datePublished ?? '').trim();
            const modified = String(article.dateModified ?? '').trim();
            const visibleDatetimes = extractTimeDatetimes(response.body);

            expect(Boolean(published), 'sample.article.date-published', 'Expected datePublished in Article JSON-LD.');
            expect(
                published === '' || visibleDatetimes.some((value) => sameInstant(value, published)),
                'sample.article.visible-published-date',
                `Expected visible <time datetime> matching datePublished=${published}.`,
            );

            if (modified && published && !sameInstant(modified, published)) {
                expect(
                    visibleDatetimes.some((value) => sameInstant(value, modified)),
                    'sample.article.visible-modified-date',
                    `Expected visible <time datetime> matching dateModified=${modified}.`,
                );
            }
        }
    } else {
        expect(
            nodes.some((node) => hasType(node, 'CollectionPage') || hasType(node, 'WebPage')),
            `sample.${kind}.collection-node`,
            `Expected CollectionPage/WebPage JSON-LD for ${kind} sample.`,
        );
    }
}

async function fetchDocument(url) {
    const response = await fetch(url, {
        redirect: 'manual',
        headers: {
            Accept: 'text/html,application/xml,application/atom+xml,text/plain;q=0.9,*/*;q=0.8',
            'User-Agent': 'PrawkoNaRaz-N6-003-SEO-Validation/1.0',
        },
    });

    return {
        status: response.status,
        headers: response.headers,
        body: await response.text(),
        url,
    };
}

async function fetchImageMeta(url) {
    const response = await fetch(url, {
        redirect: 'follow',
        headers: {
            Accept: 'image/*,*/*;q=0.8',
            'User-Agent': 'PrawkoNaRaz-N6-003-SEO-Validation/1.0',
        },
    });
    const contentType = response.headers.get('content-type') ?? '';
    const bytes = Buffer.from(await response.arrayBuffer());
    const dimensions = sniffImageDimensions(bytes, contentType);

    return {
        status: response.status,
        contentType,
        width: dimensions?.width ?? null,
        height: dimensions?.height ?? null,
    };
}

function sniffImageDimensions(bytes, contentType) {
    if (bytes.length >= 24 && bytes.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]))) {
        return { width: bytes.readUInt32BE(16), height: bytes.readUInt32BE(20) };
    }

    if (bytes.length >= 10 && (bytes.subarray(0, 6).toString('ascii') === 'GIF87a' || bytes.subarray(0, 6).toString('ascii') === 'GIF89a')) {
        return { width: bytes.readUInt16LE(6), height: bytes.readUInt16LE(8) };
    }

    if (bytes.length >= 30 && bytes.subarray(0, 4).toString('ascii') === 'RIFF' && bytes.subarray(8, 12).toString('ascii') === 'WEBP') {
        const chunk = bytes.subarray(12, 16).toString('ascii');

        if (chunk === 'VP8X') {
            return {
                width: 1 + bytes.readUIntLE(24, 3),
                height: 1 + bytes.readUIntLE(27, 3),
            };
        }
    }

    if (bytes.length >= 4 && bytes[0] === 0xff && bytes[1] === 0xd8) {
        let offset = 2;

        while (offset + 9 < bytes.length) {
            if (bytes[offset] !== 0xff) {
                offset += 1;
                continue;
            }

            const marker = bytes[offset + 1];
            const length = bytes.readUInt16BE(offset + 2);

            if ([0xc0, 0xc1, 0xc2, 0xc3, 0xc5, 0xc6, 0xc7, 0xc9, 0xca, 0xcb, 0xcd, 0xce, 0xcf].includes(marker)) {
                return {
                    height: bytes.readUInt16BE(offset + 5),
                    width: bytes.readUInt16BE(offset + 7),
                };
            }

            if (!Number.isFinite(length) || length < 2) {
                break;
            }

            offset += 2 + length;
        }
    }

    if (contentType.includes('image/svg+xml')) {
        const svg = bytes.toString('utf8', 0, Math.min(bytes.length, 64_000));
        const width = positiveNumber(extractSvgDimension(svg, 'width'));
        const height = positiveNumber(extractSvgDimension(svg, 'height'));

        if (width && height) {
            return { width, height };
        }

        const viewBox = /\bviewBox\s*=\s*["']\s*[-\d.]+\s+[-\d.]+\s+([\d.]+)\s+([\d.]+)\s*["']/i.exec(svg);

        if (viewBox) {
            return { width: Number(viewBox[1]), height: Number(viewBox[2]) };
        }
    }

    return null;
}

function extractSvgDimension(svg, name) {
    const match = new RegExp(`\\b${name}\\s*=\\s*["']([0-9.]+)(?:px)?["']`, 'i').exec(svg);
    return match?.[1] ?? null;
}

function extractCanonical(html) {
    for (const tag of html.match(/<link\b[^>]*>/gi) ?? []) {
        const attrs = parseAttributes(tag);
        const rel = String(attrs.rel ?? '').toLowerCase().split(/\s+/);

        if (rel.includes('canonical') && attrs.href) {
            return absolutize(attrs.href);
        }
    }

    return null;
}

function extractAtomDiscoveryHref(html) {
    for (const tag of html.match(/<link\b[^>]*>/gi) ?? []) {
        const attrs = parseAttributes(tag);
        const rel = String(attrs.rel ?? '').toLowerCase().split(/\s+/);
        const type = String(attrs.type ?? '').toLowerCase();

        if (rel.includes('alternate') && type.includes('application/atom+xml') && attrs.href) {
            return absolutize(attrs.href);
        }
    }

    return null;
}

function extractMetaRobots(html) {
    for (const tag of html.match(/<meta\b[^>]*>/gi) ?? []) {
        const attrs = parseAttributes(tag);
        const name = String(attrs.name ?? '').toLowerCase();

        if (name === 'robots') {
            return String(attrs.content ?? '');
        }
    }

    return '';
}

function extractMetaDescription(html) {
    for (const tag of html.match(/<meta\b[^>]*>/gi) ?? []) {
        const attrs = parseAttributes(tag);
        const name = String(attrs.name ?? '').toLowerCase();

        if (name === 'description') {
            return String(attrs.content ?? '').trim();
        }
    }

    return '';
}

function extractFirstTagText(html, tagName) {
    const match = new RegExp(`<${tagName}\\b[^>]*>([\\s\\S]*?)<\\/${tagName}>`, 'i').exec(html);

    if (!match) {
        return '';
    }

    return decodeHtml(stripTags(match[1])).replace(/\s+/g, ' ').trim();
}

function extractTimeDatetimes(html) {
    return (html.match(/<time\b[^>]*>/gi) ?? [])
        .map((tag) => parseAttributes(tag).datetime)
        .filter(Boolean)
        .map((value) => String(value));
}

function extractJsonLd(html) {
    const values = [];
    const regex = /<script\b[^>]*type\s*=\s*["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi;
    let match;

    while ((match = regex.exec(html)) !== null) {
        const raw = decodeHtml(match[1]).trim();

        if (!raw) {
            continue;
        }

        try {
            values.push(JSON.parse(raw));
        } catch (error) {
            addCheck('structured-data.parse', 'fail', `Invalid JSON-LD block: ${error instanceof Error ? error.message : String(error)}`);
        }
    }

    return values;
}

function flattenJsonLd(values) {
    const nodes = [];

    const visit = (value) => {
        if (Array.isArray(value)) {
            value.forEach(visit);
            return;
        }

        if (!value || typeof value !== 'object') {
            return;
        }

        if (Array.isArray(value['@graph'])) {
            value['@graph'].forEach(visit);
        }

        if (value['@type'] || value['@id']) {
            nodes.push(value);
        }
    };

    values.forEach(visit);

    return nodes;
}

function resolveLogo(value, nodes) {
    if (typeof value === 'string') {
        return { url: absolutize(value), width: null, height: null };
    }

    if (!value || typeof value !== 'object') {
        return null;
    }

    const directUrl = readUrlValue(value.url) ?? readUrlValue(value.contentUrl);

    if (directUrl) {
        return {
            url: absolutize(directUrl),
            width: value.width ?? null,
            height: value.height ?? null,
        };
    }

    const id = String(value['@id'] ?? '');

    if (id) {
        const node = nodes.find((candidate) => String(candidate['@id'] ?? '') === id);

        if (node) {
            const nodeUrl = readUrlValue(node.url) ?? readUrlValue(node.contentUrl);

            if (nodeUrl) {
                return {
                    url: absolutize(nodeUrl),
                    width: node.width ?? null,
                    height: node.height ?? null,
                };
            }
        }
    }

    return null;
}

function parseAttributes(tag) {
    const attrs = {};
    const regex = /([:\w-]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'=<>\x60]+)))?/g;
    let match;

    while ((match = regex.exec(tag)) !== null) {
        const name = match[1].toLowerCase();

        if (name.startsWith('<')) {
            continue;
        }

        attrs[name] = decodeHtml(match[2] ?? match[3] ?? match[4] ?? '');
    }

    return attrs;
}

function extractXmlLocs(xml) {
    return [...xml.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/gi)]
        .map((match) => decodeHtml(match[1]).trim())
        .filter(Boolean);
}

function readUrlValue(value) {
    if (typeof value === 'string') {
        return value;
    }

    if (value && typeof value === 'object') {
        if (typeof value['@id'] === 'string' && /^https?:\/\//i.test(value['@id'])) {
            return value['@id'];
        }

        if (typeof value.url === 'string') {
            return value.url;
        }
    }

    return null;
}

function referenceId(value) {
    if (typeof value === 'string') {
        return value;
    }

    if (value && typeof value === 'object' && typeof value['@id'] === 'string') {
        return value['@id'];
    }

    return null;
}

function hasType(node, expected) {
    return normalizeTypes(node?.['@type']).includes(expected);
}

function normalizeTypes(value) {
    if (Array.isArray(value)) {
        return value.map(String);
    }

    if (value == null) {
        return [];
    }

    return [String(value)];
}

function urlEquals(left, right) {
    if (!left || !right) {
        return false;
    }

    try {
        const a = new URL(left, origin);
        const b = new URL(right, origin);

        return normalizePathUrl(a) === normalizePathUrl(b);
    } catch {
        return false;
    }
}

function normalizePathUrl(url) {
    const pathName = url.pathname === '/' ? '' : url.pathname.replace(/\/+$/, '');

    return `${url.protocol}//${url.host}${pathName}${url.search}`;
}

function belongsToOrigin(url) {
    try {
        return new URL(url).origin === origin;
    } catch {
        return false;
    }
}

function pathnameLabel(url) {
    try {
        return new URL(url).pathname.replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '') || 'root';
    } catch {
        return 'invalid';
    }
}

function normalizedOptionalUrl(value) {
    const trimmed = String(value ?? '').trim();

    if (!trimmed) {
        return null;
    }

    try {
        const url = new URL(trimmed);

        if (url.origin !== origin) {
            throw new Error(`Sample URL must belong to ${origin}: ${trimmed}`);
        }

        return url.toString();
    } catch (error) {
        throw new Error(`Invalid sample URL [${trimmed}]: ${error instanceof Error ? error.message : String(error)}`);
    }
}

function absolutize(value) {
    try {
        return new URL(value, origin).toString();
    } catch {
        return value;
    }
}

function positiveNumber(value) {
    if (typeof value === 'object' && value !== null && '@value' in value) {
        return positiveNumber(value['@value']);
    }

    const number = Number.parseFloat(String(value ?? '').replace(/[^\d.]+/g, ''));

    return Number.isFinite(number) && number > 0 ? number : null;
}

function sameInstant(left, right) {
    const a = Date.parse(left);
    const b = Date.parse(right);

    return Number.isFinite(a) && Number.isFinite(b) && a === b;
}

function stripTags(value) {
    return value.replace(/<[^>]*>/g, ' ');
}

function decodeHtml(value) {
    return String(value)
        .replace(/&amp;/gi, '&')
        .replace(/&quot;/gi, '"')
        .replace(/&#39;|&apos;/gi, "'")
        .replace(/&lt;/gi, '<')
        .replace(/&gt;/gi, '>');
}

function unique(values) {
    return [...new Set(values)];
}

function expect(condition, name, failureMessage) {
    addCheck(name, condition ? 'pass' : 'fail', condition ? 'PASS' : failureMessage);
}

function addCheck(name, status, message) {
    const entry = { name, status, message };
    report.checks.push(entry);

    const prefix = status === 'pass' ? 'PASS' : status === 'warn' ? 'WARN' : 'FAIL';
    console.log(`[n6-003] ${prefix} ${name}: ${message}`);
}
