import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import http from 'node:http';
import path from 'node:path';
import process from 'node:process';
import { chromium } from 'playwright';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright', 'newsroom-article');
const reportPath = path.join(outputDir, 'report.json');
const snapshotPath = path.join(outputDir, 'article.html');
const renderStatusPath = path.join(outputDir, 'render-status.txt');
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
        for (const expected of ['Kontekst zmian i egzaminu', 'Źródła', 'E2E źródło oficjalne']) {
            if (!bodyText.includes(expected)) {
                throw new Error(`Missing "${expected}" at ${viewport.name}px.`);
            }
        }

        if (bodyText.includes('Moduł publiczny N3')) {
            throw new Error(`Deferred N3-005 placeholder leaked at ${viewport.name}px.`);
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

async function seedArticle() {
    const php = String.raw`
$existing = \App\Models\ContentArticle::query()->where('slug', '${slug}')->first();
if ($existing) { $existing->delete(); }
$article = \App\Models\ContentArticle::factory()->published()->create([
    'title' => '${title}',
    'slug' => '${slug}',
    'lead' => 'Lead E2E sprawdzający publiczny widok artykułu newsroomu na pełnej macierzy szerokości.',
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
$request = \Illuminate\Http\Request::create('${baseUrl}${articlePath}', 'GET');
$response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
$status = $response->getStatusCode();
if ($status !== 200) { throw new \RuntimeException('Newsroom article render returned HTTP '.$status); }
file_put_contents(base_path('output/playwright/newsroom-article/article.html'), $response->getContent());
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
