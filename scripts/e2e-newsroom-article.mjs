import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import http from 'node:http';
import path from 'node:path';
import process from 'node:process';
import { chromium } from 'playwright';
import { collectSnapshotPerformance } from './newsroom-performance-metrics.mjs';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright', 'newsroom-article');
const reportPath = path.join(outputDir, 'report.json');
const snapshotPath = path.join(outputDir, 'article.html');
const renderStatusPath = path.join(outputDir, 'render-status.txt');
const renderMetricsPath = path.join(outputDir, 'render-metrics.json');
const slug = 'e2e-newsroom-article';
const articlePath = `/aktualnosci/${slug}`;
const title = 'Długi testowy tytuł artykułu newsroomu sprawdzający poprawne zawijanie na małych ekranach';
const port = Number(process.env.E2E_NEWSROOM_PORT ?? '8127');
const baseUrl = `http://127.0.0.1:${port}`;
const publicDir = path.join(cwd, 'public');
const viewports = [
    { name: '360', width: 360, height: 800 },
    { name: '390', width: 390, height: 844 },
    { name: '430', width: 430, height: 932 },
    { name: '768', width: 768, height: 1024 },
    { name: '1024', width: 1024, height: 768 },
    { name: '1440', width: 1440, height: 900 },
];

const report = {
    started_at: new Date().toISOString(),
    article_path: articlePath,
    status: 'running',
    render_status: null,
    viewports: [],
    analytics: null,
    performance: null,
};

let browser;
let staticServer;

try {
    await fs.mkdir(outputDir, { recursive: true });
    console.log('[newsroom-e2e] migrate');
    await runCommand('php', ['artisan', 'migrate', '--force']);
    console.log('[newsroom-e2e] seed');
    await seedArticle();
    console.log('[newsroom-e2e] render through Laravel kernel');
    report.render_status = await renderArticleSnapshot();
    report.performance = await collectSnapshotPerformance({ cwd, baseUrl, snapshotPath, renderMetricsPath });
    console.log('[newsroom-e2e] performance', JSON.stringify(report.performance));
    console.log('[newsroom-e2e] start static browser server');
    staticServer = await startStaticServer();
    browser = await chromium.launch({ headless: true });

    for (const viewport of viewports) {
        console.log(`[newsroom-e2e] viewport ${viewport.name}`);
        const context = await browser.newContext({
            viewport: { width: viewport.width, height: viewport.height },
            serviceWorkers: 'block',
            javaScriptEnabled: false,
        });
        await context.route('https://fonts.googleapis.com/**', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'text/css; charset=utf-8',
                body: '/* Browser QA font stylesheet stub. */',
            });
        });
        const page = await context.newPage();
        page.setDefaultTimeout(10_000);
        page.setDefaultNavigationTimeout(15_000);

        const stylesheetResponsePromise = page.waitForResponse(
            (candidate) => (
                candidate.request().resourceType() === 'stylesheet'
                && candidate.url().startsWith(`${baseUrl}/build/`)
            ),
            { timeout: 15_000 },
        );
        const response = await page.goto(`${baseUrl}${articlePath}`, {
            waitUntil: 'domcontentloaded',
            timeout: 15_000,
        });

        if (!response || response.status() !== 200) {
            throw new Error(`Article snapshot returned ${response?.status() ?? 'no response'} at ${viewport.name}px.`);
        }

        const stylesheetResponse = await stylesheetResponsePromise;
        if (!stylesheetResponse.ok()) {
            throw new Error(
                `Built stylesheet returned HTTP ${stylesheetResponse.status()} at ${viewport.name}px.`,
            );
        }

        await page.waitForFunction(
            () => {
                const shell = document.querySelector('.content-shell');

                return getComputedStyle(document.body).marginLeft === '0px'
                    && shell !== null
                    && getComputedStyle(shell).boxSizing === 'border-box';
            },
            null,
            { timeout: 10_000 },
        );

        const cssHealth = await page.evaluate(() => {
            const shell = document.querySelector('.content-shell');

            return {
                bodyMarginLeft: getComputedStyle(document.body).marginLeft,
                shellBoxSizing: shell ? getComputedStyle(shell).boxSizing : null,
            };
        });

        if (cssHealth.bodyMarginLeft !== '0px' || cssHealth.shellBoxSizing !== 'border-box') {
            throw new Error(
                `Built stylesheet did not load at ${viewport.name}px: body margin ${cssHealth.bodyMarginLeft}, shell box-sizing ${cssHealth.shellBoxSizing}.`,
            );
        }

        const h1 = page.locator('h1');
        await h1.waitFor();

        if (await h1.count() !== 1 || (await h1.innerText()).trim() !== title) {
            throw new Error(`H1 contract failed at ${viewport.name}px.`);
        }

        await page.locator('nav[aria-label="Breadcrumb"]').waitFor();

        const bodyText = await page.locator('body').innerText();
        for (const expected of [
            'Kontekst zmian i egzaminu',
            'Źródła',
            'E2E źródło oficjalne',
            'Sprawdź się w teście',
        ]) {
            if (!bodyText.includes(expected)) {
                throw new Error(`Missing "${expected}" at ${viewport.name}px.`);
            }
        }

        if (bodyText.includes('Moduł publiczny N3')) {
            throw new Error(`Internal Product Bridge placeholder leaked at ${viewport.name}px.`);
        }

        const productCta = page.getByRole('link', { name: 'Sprawdź się w teście' });
        if (await productCta.count() !== 1) {
            throw new Error(`Product Bridge CTA contract failed at ${viewport.name}px.`);
        }

        const productCtaHref = await productCta.getAttribute('href');
        if (!productCtaHref?.endsWith('/testy-na-prawo-jazdy')) {
            throw new Error(`Product Bridge CTA target failed at ${viewport.name}px: ${productCtaHref}`);
        }

        const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
        if (!canonical?.endsWith(articlePath)) {
            throw new Error(`Canonical contract failed at ${viewport.name}px: ${canonical}`);
        }

        if (await page.locator('script[type="application/ld+json"]').count() < 1) {
            throw new Error(`JSON-LD is missing at ${viewport.name}px.`);
        }

        const cdp = await context.newCDPSession(page);
        const metrics = await cdp.send('Page.getLayoutMetrics');
        const scrollWidth = Math.ceil(metrics.cssContentSize.width);
        const viewportWidth = Math.ceil(metrics.cssLayoutViewport.clientWidth);

        if (scrollWidth > viewportWidth) {
            throw new Error(
                `Horizontal overflow at ${viewport.name}px: ${scrollWidth}px > ${viewportWidth}px.`,
            );
        }

        const screenshot = path.join(outputDir, `article-${viewport.name}.png`);
        await page.screenshot({ path: screenshot, fullPage: true, timeout: 15_000 });
        report.viewports.push({
            ...viewport,
            status: 'ok',
            scroll_width: scrollWidth,
            screenshot,
        });

        await context.close();
    }

    console.log('[newsroom-e2e] analytics');
    report.analytics = await verifyAnalyticsHooks(browser);

    report.status = 'ok';
    console.log('[newsroom-e2e] PASS');
} catch (error) {
    report.status = 'failed';
    report.error = error instanceof Error ? error.message : String(error);
    console.error('[newsroom-e2e] FAIL', report.error);
    throw error;
} finally {
    report.finished_at = new Date().toISOString();
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
    await browser?.close().catch(() => {});
    await closeStaticServer(staticServer);
}

async function verifyAnalyticsHooks(browserInstance) {
    const context = await browserInstance.newContext({
        viewport: { width: 1280, height: 900 },
        serviceWorkers: 'block',
        javaScriptEnabled: true,
    });
    const page = await context.newPage();
    page.setDefaultTimeout(10_000);
    page.setDefaultNavigationTimeout(15_000);

    const response = await page.goto(`${baseUrl}${articlePath}`, {
        waitUntil: 'domcontentloaded',
        timeout: 15_000,
    });

    if (!response || response.status() !== 200) {
        throw new Error(`Analytics snapshot returned ${response?.status() ?? 'no response'}.`);
    }

    await page.waitForFunction(() => {
        return Boolean(document.querySelector('[data-newsroom-analytics-article]'))
            && Boolean(document.querySelector('[data-newsroom-analytics-event="newsroom_product_cta_click"]'))
            && Boolean(document.querySelector('[data-newsroom-analytics-event="newsroom_source_click"]'))
            && Boolean(document.querySelector('[data-newsroom-analytics-event="newsroom_related_article_click"]'));
    });

    await page.evaluate(() => {
        window.__newsroomAnalyticsEvents = [];
        window.__prawkonarazGoogleAnalyticsConfigured = 'G-E2E';
        window.gtag = (...args) => {
            window.__newsroomAnalyticsEvents.push(args);
        };
        window.dispatchEvent(new CustomEvent('prawkonaraz:analytics-ready'));
    });

    await page.waitForFunction(() => {
        return window.__newsroomAnalyticsEvents?.filter(
            (entry) => entry[0] === 'event' && entry[1] === 'newsroom_article_view',
        ).length === 1;
    });

    const clickWithoutNavigation = async (selector) => {
        await page.evaluate((targetSelector) => {
            const link = document.querySelector(targetSelector);

            if (!(link instanceof HTMLAnchorElement)) {
                throw new Error(`Analytics link not found: ${targetSelector}`);
            }

            link.addEventListener('click', (event) => event.preventDefault(), { once: true });
            link.click();
        }, selector);
    };

    await clickWithoutNavigation('[data-newsroom-analytics-event="newsroom_product_cta_click"]');
    await clickWithoutNavigation('[data-newsroom-analytics-event="newsroom_source_click"]');
    await clickWithoutNavigation('[data-newsroom-analytics-event="newsroom_related_article_click"]');

    await page.evaluate(() => {
        const moduleCard = document.createElement('article');
        moduleCard.dataset.newsroomAnalyticsModule = 'latest';
        moduleCard.dataset.newsroomAnalyticsPosition = '2';
        moduleCard.dataset.articleId = '987654';
        moduleCard.dataset.articleType = 'news';
        moduleCard.dataset.categorySlug = 'egzaminy';
        moduleCard.dataset.articleUrl = '/aktualnosci/e2e-module-target';

        const moduleLink = document.createElement('a');
        moduleLink.href = '/aktualnosci/e2e-module-target';
        moduleLink.textContent = 'Synthetic module target';
        moduleLink.addEventListener('click', (event) => event.preventDefault(), { once: true });
        moduleCard.appendChild(moduleLink);
        document.body.appendChild(moduleCard);
        moduleLink.click();

        const nonLink = document.createElement('button');
        nonLink.dataset.newsroomAnalyticsEvent = 'newsroom_product_cta_click';
        nonLink.click();

        const disabledLink = document.createElement('a');
        disabledLink.href = '/testy-na-prawo-jazdy';
        disabledLink.dataset.newsroomAnalyticsEvent = 'newsroom_product_cta_click';
        disabledLink.setAttribute('aria-disabled', 'true');
        disabledLink.addEventListener('click', (event) => event.preventDefault(), { once: true });
        document.body.appendChild(disabledLink);
        disabledLink.click();
    });

    const events = await page.evaluate(() => (
        (window.__newsroomAnalyticsEvents ?? [])
            .filter((entry) => entry[0] === 'event')
            .map((entry) => ({
                name: entry[1],
                parameters: entry[2] ?? {},
            }))
    ));

    const expectedSingleEvents = [
        'newsroom_article_view',
        'newsroom_product_cta_click',
        'newsroom_source_click',
        'newsroom_related_article_click',
        'newsroom_module_click',
    ];

    for (const eventName of expectedSingleEvents) {
        const matches = events.filter((event) => event.name === eventName);

        if (matches.length !== 1) {
            throw new Error(`${eventName} expected exactly once, got ${matches.length}.`);
        }
    }

    for (const event of events) {
        for (const forbidden of ['title', 'author', 'body', 'lead', 'source_title', 'source_publisher']) {
            if (Object.prototype.hasOwnProperty.call(event.parameters, forbidden)) {
                throw new Error(`Forbidden analytics parameter "${forbidden}" in ${event.name}.`);
            }
        }
    }

    const articleView = events.find((event) => event.name === 'newsroom_article_view');

    if (!Number.isInteger(articleView?.parameters?.article_id)
        || articleView?.parameters?.article_type !== 'news'
        || typeof articleView?.parameters?.category_slug !== 'string') {
        throw new Error('Article view is missing stable article context.');
    }

    const productClick = events.find((event) => event.name === 'newsroom_product_cta_click');
    if (productClick?.parameters?.module !== 'product_bridge'
        || productClick?.parameters?.destination_path !== '/testy-na-prawo-jazdy') {
        throw new Error('Product CTA analytics parameters are invalid.');
    }

    const sourceClick = events.find((event) => event.name === 'newsroom_source_click');
    if (sourceClick?.parameters?.module !== 'sources'
        || sourceClick?.parameters?.destination_path !== '/e2e-source') {
        throw new Error('Source analytics parameters are invalid.');
    }

    const relatedClick = events.find((event) => event.name === 'newsroom_related_article_click');
    if (relatedClick?.parameters?.module !== 'related_articles'
        || !relatedClick?.parameters?.destination_path?.endsWith('/e2e-powiazany-material-analityczny')) {
        throw new Error('Related article analytics parameters are invalid.');
    }

    const moduleClick = events.find((event) => event.name === 'newsroom_module_click');
    if (moduleClick?.parameters?.article_id !== 987654
        || moduleClick?.parameters?.article_type !== 'news'
        || moduleClick?.parameters?.category_slug !== 'egzaminy'
        || moduleClick?.parameters?.module !== 'latest'
        || moduleClick?.parameters?.position !== '2'
        || moduleClick?.parameters?.destination_path !== '/aktualnosci/e2e-module-target') {
        throw new Error('Module click analytics parameters are invalid.');
    }

    await context.close();

    return {
        status: 'ok',
        event_names: events.map((event) => event.name),
        article_id: articleView.parameters.article_id,
    };
}
async function seedArticle() {
    const php = String.raw`
$existing = \App\Models\ContentArticle::query()->where('slug', '${slug}')->first();
if ($existing) { $existing->delete(); }
$related = \App\Models\ContentArticle::factory()->published()->create([
    'title' => 'E2E powiązany materiał analityczny',
    'slug' => 'e2e-powiazany-material-analityczny',
]);
$article = \App\Models\ContentArticle::factory()->published()->create([
    'title' => '${title}',
    'slug' => '${slug}',
    'lead' => 'Lead E2E sprawdzający publiczny widok artykułu newsroomu na pełnej macierzy szerokości.',
    'body_blocks' => \App\Support\NewsroomBodyContract::normalize([
        [
            'type' => \App\Support\NewsroomBodyContract::BLOCK_RELATED_ARTICLE,
            'data' => ['article_id' => $related->id],
        ],
        [
            'type' => \App\Support\NewsroomBodyContract::BLOCK_PRODUCT_CTA,
            'data' => ['kind' => 'test'],
        ],
    ]),
    'key_points' => ['Pierwszy punkt E2E', 'Drugi punkt E2E'],
    'regulatory_status' => 'in_force',
    'effective_from' => now()->subDay()->toDateString(),
    'change_summary' => 'Zmiana testowa widoczna publicznie.',
    'applies_to' => 'Kandydaci na kierowców kategorii B.',
    'exam_impact' => 'Kontekst testowy do sprawdzenia widoku egzaminacyjnego.',
]);
\App\Models\ContentArticleSource::factory()->for($article, 'article')->create([
    'title' => 'E2E źródło oficjalne',
    'publisher' => 'Źródło testowe',
    'url' => 'https://example.test/e2e-source',
    'is_publicly_cited' => true,
    'is_official' => true,
]);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);
}

async function renderArticleSnapshot() {
    const php = String.raw`
$connection = \Illuminate\Support\Facades\DB::connection();
$connection->flushQueryLog();
$connection->enableQueryLog();
$startedAt = hrtime(true);
$request = \Illuminate\Http\Request::create('${baseUrl}${articlePath}', 'GET');
$response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
$durationMs = (hrtime(true) - $startedAt) / 1000000;
$queryCount = count($connection->getQueryLog());
$connection->disableQueryLog();
$status = $response->getStatusCode();
if ($status !== 200) { throw new \RuntimeException('Newsroom article render returned HTTP '.$status); }
$content = (string) $response->getContent();
file_put_contents(base_path('output/playwright/newsroom-article/article.html'), $content);
file_put_contents(base_path('output/playwright/newsroom-article/render-metrics.json'), json_encode([
    'kernel_render_ms' => round($durationMs, 2),
    'query_count' => $queryCount,
    'html_bytes' => strlen($content),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
app(\Illuminate\Contracts\Http\Kernel::class)->terminate($request, $response);
file_put_contents(base_path('output/playwright/newsroom-article/render-status.txt'), (string) $status);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);
    const status = Number((await fs.readFile(renderStatusPath, 'utf8')).trim());

    if (status !== 200) {
        throw new Error(`Laravel kernel render returned ${status}.`);
    }

    const snapshotHtml = await fs.readFile(snapshotPath, 'utf8');
    const normalizedHtml = snapshotHtml.replace(
        /https?:\/\/[^/"']+\/build\//g,
        `${baseUrl}/build/`,
    );
    await fs.writeFile(snapshotPath, normalizedHtml);

    return status;
}

async function startStaticServer() {
    return await new Promise((resolve, reject) => {
        const server = http.createServer(async (request, response) => {
            try {
                const url = new URL(request.url ?? '/', baseUrl);
                const filePath = resolveStaticPath(url.pathname);
                const body = await fs.readFile(filePath);

                response.writeHead(200, {
                    'Content-Type': contentType(filePath),
                    'Cache-Control': 'no-store',
                });
                response.end(body);
            } catch {
                response.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
                response.end('Not found');
            }
        });

        server.once('error', reject);
        server.listen(port, '127.0.0.1', () => resolve(server));
    });
}

function resolveStaticPath(pathname) {
    if (pathname === articlePath) {
        return snapshotPath;
    }

    const candidate = path.resolve(publicDir, `.${decodeURIComponent(pathname)}`);
    const publicPrefix = `${path.resolve(publicDir)}${path.sep}`;

    if (!candidate.startsWith(publicPrefix)) {
        throw new Error(`Refusing path outside public directory: ${pathname}`);
    }

    return candidate;
}

function contentType(filePath) {
    switch (path.extname(filePath).toLowerCase()) {
        case '.css': return 'text/css; charset=utf-8';
        case '.js': return 'text/javascript; charset=utf-8';
        case '.json': return 'application/json; charset=utf-8';
        case '.svg': return 'image/svg+xml';
        case '.png': return 'image/png';
        case '.jpg':
        case '.jpeg': return 'image/jpeg';
        case '.webp': return 'image/webp';
        case '.avif': return 'image/avif';
        case '.woff2': return 'font/woff2';
        case '.ico': return 'image/x-icon';
        case '.html': return 'text/html; charset=utf-8';
        default: return 'application/octet-stream';
    }
}

async function closeStaticServer(server) {
    if (!server) return;

    await new Promise((resolve) => server.close(() => resolve()));
}

async function runCommand(command, args) {
    await new Promise((resolve, reject) => {
        const child = spawn(command, args, { cwd, env: process.env, stdio: 'inherit' });
        child.once('error', reject);
        child.once('exit', (code) => {
            if (code === 0) resolve();
            else reject(new Error(`${command} ${args.join(' ')} exited with code ${code}.`));
        });
    });
}
