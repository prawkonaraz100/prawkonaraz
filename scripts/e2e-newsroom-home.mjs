import fs from 'node:fs/promises';
import http from 'node:http';
import path from 'node:path';
import process from 'node:process';
import { spawn } from 'node:child_process';
import { chromium } from 'playwright';
import { collectSnapshotPerformance } from './newsroom-performance-metrics.mjs';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright', 'newsroom-home');
const snapshotPath = path.join(outputDir, 'home.html');
const reportPath = path.join(outputDir, 'report.json');
const renderStatusPath = path.join(outputDir, 'render-status.txt');
const renderMetricsPath = path.join(outputDir, 'render-metrics.json');
const homePath = '/aktualnosci';
const leadTitle = 'Najważniejsza informacja dnia dla kandydatów na kierowców';
const port = Number(process.env.E2E_NEWSROOM_HOME_PORT ?? '8128');
const baseUrl = `http://127.0.0.1:${port}`;
const publicDir = path.join(cwd, 'public');
const viewports = [
    { name: '360', width: 360, height: 800 },
    { name: '390', width: 390, height: 844 },
    { name: '430', width: 430, height: 932 },
    { name: '768', width: 768, height: 1024 },
    { name: '1024', width: 1024, height: 768 },
    { name: '1280', width: 1280, height: 800 },
    { name: '1440', width: 1440, height: 900 },
];

const report = {
    started_at: new Date().toISOString(),
    path: homePath,
    status: 'running',
    render_status: null,
    viewports: [],
    performance: null,
};

let browser;
let staticServer;

try {
    await fs.mkdir(outputDir, { recursive: true });

    console.log('[newsroom-home-e2e] migrate');
    await runCommand('php', ['artisan', 'migrate', '--force']);

    console.log('[newsroom-home-e2e] seed');
    await seedHome();

    console.log('[newsroom-home-e2e] render through Laravel kernel');
    report.render_status = await renderHomeSnapshot();
    report.performance = await collectSnapshotPerformance({ cwd, baseUrl, snapshotPath, renderMetricsPath });
    console.log('[newsroom-home-e2e] performance', JSON.stringify(report.performance));

    console.log('[newsroom-home-e2e] start static browser server');
    staticServer = await startStaticServer();
    browser = await chromium.launch({ headless: true });

    for (const viewport of viewports) {
        console.log(`[newsroom-home-e2e] viewport ${viewport.name}`);
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

        const response = await page.goto(`${baseUrl}${homePath}`, {
            waitUntil: 'domcontentloaded',
            timeout: 15_000,
        });

        if (!response || response.status() !== 200) {
            throw new Error(`Newsroom home snapshot returned ${response?.status() ?? 'no response'} at ${viewport.name}px.`);
        }

        const stylesheetResponse = await stylesheetResponsePromise;
        if (!stylesheetResponse.ok()) {
            throw new Error(`Built stylesheet returned HTTP ${stylesheetResponse.status()} at ${viewport.name}px.`);
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

        const h1 = page.locator('h1');
        if (await h1.count() !== 1 || (await h1.innerText()).trim() !== 'Aktualności') {
            throw new Error(`H1 contract failed at ${viewport.name}px.`);
        }

        if (await page.getByRole('navigation', { name: 'Sekcje aktualności' }).count() !== 1) {
            throw new Error(`Newsroom subnavigation is missing at ${viewport.name}px.`);
        }

        const bodyText = await page.locator('body').innerText();
        for (const expected of [
            leadTitle,
            'Najnowsze',
            'Egzaminy',
            'Poradniki',
            'Czy zdałbyś teorię dzisiaj?',
            'Rozpocznij bezpłatny test',
        ]) {
            if (!bodyText.includes(expected)) {
                throw new Error(`Missing "${expected}" at ${viewport.name}px.`);
            }
        }

        if (bodyText.includes('Tu pojawią się aktualności') || bodyText.includes('Brak kart')) {
            throw new Error(`Placeholder or fake empty-state copy leaked at ${viewport.name}px.`);
        }

        const productCta = page.getByRole('link', { name: 'Rozpocznij bezpłatny test' });
        if (await productCta.count() !== 1) {
            throw new Error(`Hub Product Bridge CTA contract failed at ${viewport.name}px.`);
        }

        const productCtaHref = await productCta.getAttribute('href');
        if (!productCtaHref?.endsWith('/testy-na-prawo-jazdy')) {
            throw new Error(`Hub Product Bridge target failed at ${viewport.name}px: ${productCtaHref}`);
        }

        const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
        if (!canonical?.endsWith(homePath)) {
            throw new Error(`Hub canonical contract failed at ${viewport.name}px: ${canonical}`);
        }

        const robots = await page.locator('meta[name="robots"]').getAttribute('content');
        if (robots !== 'index,follow,max-image-preview:large') {
            throw new Error(`Hub robots contract failed at ${viewport.name}px: ${robots}`);
        }

        const cdp = await context.newCDPSession(page);
        const metrics = await cdp.send('Page.getLayoutMetrics');
        const scrollWidth = Math.ceil(metrics.cssContentSize.width);
        const viewportWidth = Math.ceil(metrics.cssLayoutViewport.clientWidth);

        if (scrollWidth > viewportWidth) {
            throw new Error(`Horizontal overflow at ${viewport.name}px: ${scrollWidth}px > ${viewportWidth}px.`);
        }

        const screenshot = path.join(outputDir, `home-${viewport.name}.png`);
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
    console.log('[newsroom-home-e2e] PASS');
} catch (error) {
    report.status = 'failed';
    report.error = error instanceof Error ? error.message : String(error);
    console.error('[newsroom-home-e2e] FAIL', report.error);
    throw error;
} finally {
    report.finished_at = new Date().toISOString();
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
    await browser?.close().catch(() => {});
    await closeStaticServer(staticServer);
}

async function seedHome() {
    const php = String.raw`
$categories = [
    ['name' => 'Egzaminy', 'slug' => 'egzaminy', 'position' => 10],
    ['name' => 'Przepisy', 'slug' => 'przepisy', 'position' => 20],
    ['name' => 'WORD', 'slug' => 'word', 'position' => 30],
];

$models = [];
foreach ($categories as $category) {
    $models[$category['slug']] = \App\Models\ContentCategory::factory()->create($category);
}

\App\Models\ContentArticle::factory()->published()->featured()->create([
    'category_id' => $models['egzaminy']->id,
    'title' => '${leadTitle}',
    'slug' => 'najwazniejsza-informacja-dnia-e2e',
    'lead' => 'Najważniejsze informacje zebrane w jednym miejscu, bez zbędnego marketingu.',
    'editorial_priority' => 1000,
    'first_published_at' => now()->subMinute(),
    'published_at' => now()->subMinute(),
]);

$newsCategories = ['egzaminy', 'przepisy', 'word'];
for ($i = 1; $i <= 40; $i++) {
    $slug = $newsCategories[($i - 1) % count($newsCategories)];
    \App\Models\ContentArticle::factory()->published()->create([
        'category_id' => $models[$slug]->id,
        'title' => 'Materiał informacyjny E2E '.$i,
        'slug' => 'material-informacyjny-e2e-'.$i,
        'lead' => 'Krótki opis materiału E2E '.$i.'.',
        'editorial_priority' => 500 - $i,
        'first_published_at' => now()->subMinutes($i + 1),
        'published_at' => now()->subMinutes($i + 1),
    ]);
}

for ($i = 1; $i <= 10; $i++) {
    \App\Models\ContentArticle::factory()->published()->guide()->create([
        'category_id' => $models['egzaminy']->id,
        'title' => 'Poradnik praktyczny E2E '.$i,
        'slug' => 'poradnik-praktyczny-e2e-'.$i,
        'lead' => 'Praktyczny materiał evergreen '.$i.'.',
        'editorial_priority' => 100 - $i,
        'first_published_at' => now()->subDays($i),
        'published_at' => now()->subDays($i),
    ]);
}
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);
}

async function renderHomeSnapshot() {
    const php = String.raw`
$connection = \Illuminate\Support\Facades\DB::connection();
$connection->flushQueryLog();
$connection->enableQueryLog();
$startedAt = hrtime(true);
$request = \Illuminate\Http\Request::create('${baseUrl}${homePath}', 'GET');
$response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
$durationMs = (hrtime(true) - $startedAt) / 1000000;
$queryCount = count($connection->getQueryLog());
$connection->disableQueryLog();
$status = $response->getStatusCode();
if ($status !== 200) { throw new \RuntimeException('Newsroom home render returned HTTP '.$status); }
$content = (string) $response->getContent();
file_put_contents(base_path('output/playwright/newsroom-home/home.html'), $content);
file_put_contents(base_path('output/playwright/newsroom-home/render-metrics.json'), json_encode([
    'kernel_render_ms' => round($durationMs, 2),
    'query_count' => $queryCount,
    'html_bytes' => strlen($content),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
app(\Illuminate\Contracts\Http\Kernel::class)->terminate($request, $response);
file_put_contents(base_path('output/playwright/newsroom-home/render-status.txt'), (string) $status);
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
    if (pathname === homePath) {
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
        case '.json': return 'application/json';
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
