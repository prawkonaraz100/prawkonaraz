import fs from 'node:fs/promises';
import path from 'node:path';

export async function collectSnapshotPerformance({ cwd, baseUrl, snapshotPath, renderMetricsPath }) {
    const html = await fs.readFile(snapshotPath, 'utf8');
    const renderMetrics = JSON.parse(await fs.readFile(renderMetricsPath, 'utf8'));

    return {
        ...renderMetrics,
        normalized_html_bytes: Buffer.byteLength(html),
        images: await collectImages(html, { cwd, baseUrl }),
        build_assets: await collectBuildAssets(html, { cwd, baseUrl }),
    };
}

export function assertPerformanceBudget(label, metrics, budget) {
    const failures = [];

    if (metrics.query_count > budget.max_query_count) {
        failures.push(`query_count ${metrics.query_count} > ${budget.max_query_count}`);
    }
    if (metrics.normalized_html_bytes > budget.max_html_bytes) {
        failures.push(`html_bytes ${metrics.normalized_html_bytes} > ${budget.max_html_bytes}`);
    }
    if (metrics.images.local_count < 1) {
        failures.push('no local image asset was measured');
    }
    if (metrics.images.missing_intrinsic_dimensions > 0) {
        failures.push(`images missing intrinsic dimensions: ${metrics.images.missing_intrinsic_dimensions}`);
    }
    if (metrics.images.missing_declared_dimensions > 0) {
        failures.push(`images missing declared dimensions: ${metrics.images.missing_declared_dimensions}`);
    }
    if (metrics.images.total_bytes > budget.max_image_bytes) {
        failures.push(`image_bytes ${metrics.images.total_bytes} > ${budget.max_image_bytes}`);
    }
    if (metrics.build_assets.css_bytes > budget.max_css_bytes) {
        failures.push(`css_bytes ${metrics.build_assets.css_bytes} > ${budget.max_css_bytes}`);
    }
    if (metrics.build_assets.js_bytes > budget.max_js_bytes) {
        failures.push(`js_bytes ${metrics.build_assets.js_bytes} > ${budget.max_js_bytes}`);
    }

    if (failures.length > 0) {
        throw new Error(`${label} performance budget failed: ${failures.join('; ')}`);
    }
}

async function collectImages(html, context) {
    const tags = [...html.matchAll(/<img\b[^>]*>/gi)].map((match) => match[0]);
    const uniqueTags = [...new Map(tags.map((tag) => [readAttribute(tag, 'src') ?? tag, tag])).values()];
    const items = [];
    const unresolved = [];

    for (const tag of uniqueTags) {
        const src = readAttribute(tag, 'src');
        const filePath = src ? resolvePublicAsset(src, context) : null;

        if (!src || !filePath) {
            unresolved.push({ src: src ?? null, reason: src ? 'external-or-non-public' : 'missing-src' });
            continue;
        }

        try {
            const bytes = await fs.readFile(filePath);
            const dimensions = sniffImageDimensions(bytes, path.extname(filePath).toLowerCase());
            items.push({
                src,
                public_path: toPublicPath(filePath, context.cwd),
                bytes: bytes.length,
                width: dimensions?.width ?? null,
                height: dimensions?.height ?? null,
                declared_width: positiveInteger(readAttribute(tag, 'width')),
                declared_height: positiveInteger(readAttribute(tag, 'height')),
            });
        } catch (error) {
            unresolved.push({ src, reason: error instanceof Error ? error.message : String(error) });
        }
    }

    return {
        referenced_count: tags.length,
        local_count: items.length,
        unique_reference_count: uniqueTags.length,
        total_bytes: items.reduce((total, item) => total + item.bytes, 0),
        missing_intrinsic_dimensions: items.filter((item) => item.width === null || item.height === null).length,
        missing_declared_dimensions: items.filter((item) => item.declared_width === null || item.declared_height === null).length,
        items,
        unresolved,
    };
}

async function collectBuildAssets(html, context) {
    const refs = unique(
        [...html.matchAll(/\b(?:src|href)\s*=\s*["']([^"']*\/build\/[^"']+)["']/gi)]
            .map((match) => match[1]),
    );
    const items = [];
    const unresolved = [];

    for (const ref of refs) {
        const filePath = resolvePublicAsset(ref, context);
        if (!filePath) {
            unresolved.push({ url: ref, reason: 'external-or-non-public' });
            continue;
        }

        try {
            const stat = await fs.stat(filePath);
            const extension = path.extname(filePath).toLowerCase();
            items.push({
                url: ref,
                public_path: toPublicPath(filePath, context.cwd),
                bytes: stat.size,
                type: extension === '.js' ? 'js' : extension === '.css' ? 'css' : 'other',
            });
        } catch (error) {
            unresolved.push({ url: ref, reason: error instanceof Error ? error.message : String(error) });
        }
    }

    return {
        referenced_count: refs.length,
        total_bytes: items.reduce((total, item) => total + item.bytes, 0),
        js_bytes: items.filter((item) => item.type === 'js').reduce((total, item) => total + item.bytes, 0),
        css_bytes: items.filter((item) => item.type === 'css').reduce((total, item) => total + item.bytes, 0),
        items,
        unresolved,
    };
}

function resolvePublicAsset(value, { cwd, baseUrl }) {
    let url;
    try {
        url = new URL(value, baseUrl);
    } catch {
        return null;
    }

    const base = new URL(baseUrl);
    if (!['http:', 'https:'].includes(url.protocol) || ![base.host, 'prawkonaraz.pl'].includes(url.host)) {
        return null;
    }

    const publicDir = path.resolve(cwd, 'public');
    const candidate = path.resolve(publicDir, `.${decodeURIComponent(url.pathname)}`);
    const prefix = `${publicDir}${path.sep}`;

    return candidate === publicDir || candidate.startsWith(prefix) ? candidate : null;
}

function readAttribute(tag, name) {
    return tag.match(new RegExp(`\\b${name}\\s*=\\s*(["'])(.*?)\\1`, 'i'))?.[2] ?? null;
}

function positiveInteger(value) {
    const number = Number.parseInt(String(value ?? ''), 10);
    return Number.isFinite(number) && number > 0 ? number : null;
}

function positiveNumber(value) {
    const number = Number.parseFloat(String(value ?? '').replace(/[^\d.]+/g, ''));
    return Number.isFinite(number) && number > 0 ? number : null;
}

function toPublicPath(filePath, cwd) {
    return path.relative(path.resolve(cwd, 'public'), filePath).replaceAll('\\', '/');
}

function sniffImageDimensions(bytes, extension) {
    if (bytes.length >= 24 && bytes.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]))) {
        return { width: bytes.readUInt32BE(16), height: bytes.readUInt32BE(20) };
    }

    if (bytes.length >= 10 && ['GIF87a', 'GIF89a'].includes(bytes.subarray(0, 6).toString('ascii'))) {
        return { width: bytes.readUInt16LE(6), height: bytes.readUInt16LE(8) };
    }

    if (bytes.length >= 30 && bytes.subarray(0, 4).toString('ascii') === 'RIFF' && bytes.subarray(8, 12).toString('ascii') === 'WEBP') {
        const chunk = bytes.subarray(12, 16).toString('ascii');

        if (chunk === 'VP8X') {
            return { width: 1 + bytes.readUIntLE(24, 3), height: 1 + bytes.readUIntLE(27, 3) };
        }

        if (chunk === 'VP8 ' && bytes.subarray(23, 26).equals(Buffer.from([0x9d, 0x01, 0x2a]))) {
            return {
                width: bytes.readUInt16LE(26) & 0x3fff,
                height: bytes.readUInt16LE(28) & 0x3fff,
            };
        }

        if (chunk === 'VP8L' && bytes[20] === 0x2f) {
            const bits = bytes.readUInt32LE(21);
            return {
                width: 1 + (bits & 0x3fff),
                height: 1 + ((bits >>> 14) & 0x3fff),
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
                return { width: bytes.readUInt16BE(offset + 7), height: bytes.readUInt16BE(offset + 5) };
            }
            if (length < 2) break;
            offset += 2 + length;
        }
    }

    if (extension === '.svg') {
        const svg = bytes.toString('utf8');
        const width = positiveNumber(readSvgAttribute(svg, 'width'));
        const height = positiveNumber(readSvgAttribute(svg, 'height'));
        if (width && height) return { width, height };

        const parts = readSvgAttribute(svg, 'viewBox')?.trim().split(/[\s,]+/).map(Number);
        if (parts?.length === 4 && parts.every(Number.isFinite) && parts[2] > 0 && parts[3] > 0) {
            return { width: parts[2], height: parts[3] };
        }
    }

    return null;
}

function readSvgAttribute(svg, name) {
    return svg.match(new RegExp(`\\b${name}\\s*=\\s*(["'])(.*?)\\1`, 'i'))?.[2] ?? null;
}

function unique(values) {
    return [...new Set(values)];
}
