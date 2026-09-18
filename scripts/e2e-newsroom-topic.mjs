import fs from 'node:fs/promises';
import http from 'node:http';
import path from 'node:path';
import process from 'node:process';
import { spawn } from 'node:child_process';
import { chromium } from 'playwright';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright', 'newsroom-topic');
const pageOneSnapshot = path.join(outputDir, 'topic-page-1.html');
const pageTwoSnapshot = path.join(outputDir, 'topic-page-2.html');
const reportPath = path.join(outputDir, 'report.json');
const topicPath = '/aktualnosci/temat/egzamin-teoretyczny';
const port = Number(process.env.E2E_NEWSROOM_TOPIC_PORT ?? '8131');
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
    path: topicPath,
    status: 'running',
    viewports: [],
    pagination: null,
};

let browser;
let staticServer;

try {
    await fs.mkdir(outputDir, { recursive: true });

    console.log('[newsroom-topic-e2e] migrate');
    await runCommand('php', ['artisan', 'migrate', '--force']);

    console.log('[newsroom-topic-e2e] seed');
    await seedTopic();

    console.log('[newsroom-topic-e2e] render SSR snapshots');
    await renderSnapshot(topicPath, pageOneSnapshot);
    await renderSnapshot(`${topicPath}?page=2`, pageTwoSnapshot);

    staticServer = await startStaticServer();
    browser = await chromium.launch({ headless: true });

    for (const viewport of viewports) {
        console.log(`[newsroom-topic-e2e] viewport ${viewport.name}`);
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

        const response = await page.goto(`${baseUrl}${topicPath}`, {
            waitUntil: 'domcontentloaded',
            timeout: 15_000,
        });

        if (!response || response.status() !== 200) {
            throw new Error(`Topic snapshot returned ${response?.status() ?? 'no response'} at ${viewport.name}px.`);
        }

        const stylesheetResponse = await stylesheetResponsePromise;
        if (!stylesheetResponse.ok()) {
            throw new Error(`Built stylesheet returned HTTP ${stylesheetResponse.status()} at ${viewport.name}px.`);
        }

        const h1 = page.locator('h1');
        if (await h1.count() !== 1 || (await h1.innerText()).trim() !== 'Egzamin teoretyczny') {
            throw new Error(`Topic H1 contract failed at ${viewport.name}px.`);
        }

        const bodyText = await page.locator('body').innerText();
        for (const expected of [
            'Najważniejsze informacje i praktyczne materiały o egzaminie teoretycznym.',
            'Wyróżniony materiał',
            'Materiał topicu E2E 22',
            'Materiały w temacie',
            'Materiał topicu E2E 1',
            'Poradnik',
            'Następna',
        ]) {
            if (!bodyText.includes(expected)) {
                throw new Error(`Missing "${expected}" at ${viewport.name}px.`);
            }
        }

        const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
        if (!canonical?.endsWith(topicPath)) {
            throw new Error(`Topic canonical failed at ${viewport.name}px: ${canonical}`);
        }

        const cdp = await context.newCDPSession(page);
        const metrics = await cdp.send('Page.getLayoutMetrics');
        const scrollWidth = Math.ceil(metrics.cssContentSize.width);
        const viewportWidth = Math.ceil(metrics.cssLayoutViewport.clientWidth);

        if (scrollWidth > viewportWidth) {
            throw new Error(`Horizontal overflow at ${viewport.name}px: ${scrollWidth}px > ${viewportWidth}px.`);
        }

        const screenshot = path.join(outputDir, `topic-${viewport.name}.png`);
        await page.screenshot({ path: screenshot, fullPage: true, timeout: 15_000 });

        report.viewports.push({
            ...viewport,
            status: 'ok',
            scroll_width: scrollWidth,
            screenshot,
        });

        await context.close();
    }

    const paginationContext = await browser.newContext({
        viewport: { width: 1024, height: 768 },
        serviceWorkers: 'block',
        javaScriptEnabled: false,
    });
    const paginationPage = await paginationContext.newPage();
    const pageTwoResponse = await paginationPage.goto(`${baseUrl}${topicPath}?page=2`, {
        waitUntil: 'domcontentloaded',
        timeout: 15_000,
    });

    if (!pageTwoResponse || pageTwoResponse.status() !== 200) {
        throw new Error('Topic page 2 snapshot did not return HTTP 200.');
    }

    const pageTwoCanonical = await paginationPage.locator('link[rel="canonical"]').getAttribute('href');
    const pageTwoText = await paginationPage.locator('body').innerText();

    if (!pageTwoCanonical?.endsWith(`${topicPath}?page=2`)) {
        throw new Error(`Topic page 2 canonical failed: ${pageTwoCanonical}`);
    }

    if (
        !pageTwoText.includes('Materiał topicu E2E 21')
        || !pageTwoText.includes('Poprzednia')
        || pageTwoText.includes('Wyróżniony materiał')
    ) {
        throw new Error('Topic page 2 content/pagination contract failed.');
    }

    report.pagination = {
        status: 'ok',
        canonical: pageTwoCanonical,
    };

    await paginationContext.close();

    report.status = 'ok';
    console.log('[newsroom-topic-e2e] PASS');
} catch (error) {
    report.status = 'failed';
    report.error = error instanceof Error ? error.message : String(error);
    console.error('[newsroom-topic-e2e] FAIL', report.error);
    throw error;
} finally {
    report.finished_at = new Date().toISOString();
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
    await browser?.close().catch(() => {});
    await closeStaticServer(staticServer);
}

async function seedTopic() {
    const php = String.raw`
$category = \App\Models\ContentCategory::factory()->create([
    'name' => 'Egzaminy',
    'slug' => 'egzaminy-topic-e2e',
]);

$topic = \App\Models\ContentTopic::factory()->published()->create([
    'title' => 'Egzamin teoretyczny',
    'slug' => 'egzamin-teoretyczny',
    'description' => 'Najważniejsze informacje i praktyczne materiały o egzaminie teoretycznym.',
    'seo_title' => 'Egzamin teoretyczny — dossier',
    'seo_description' => 'Aktualne informacje i poradniki dotyczące egzaminu teoretycznego.',
]);

$articles = collect();

for ($i = 1; $i <= 22; $i++) {
    $factory = \App\Models\ContentArticle::factory()->published();

    if ($i === 2) {
        $factory = $factory->guide();
    }

    $article = $factory->create([
        'category_id' => $category->id,
        'title' => 'Materiał topicu E2E '.$i,
        'slug' => 'material-topicu-e2e-'.$i,
        'lead' => 'Krótki opis materiału topicu '.$i.'.',
        'published_at' => now()->subMinutes($i),
        'first_published_at' => now()->subMinutes($i),
    ]);

    $articles->push($article);
}

$topic->articles()->attach($articles->pluck('id')->all());
$topic->update(['featured_article_id' => $articles->last()->id]);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);
}

async function renderSnapshot(requestPath, outputPath) {
    const relativeOutput = path.relative(cwd, outputPath).replaceAll('\\', '/');
    const php = String.raw`
$request = \Illuminate\Http\Request::create('${baseUrl}${requestPath}', 'GET');
$response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
$status = $response->getStatusCode();
if ($status !== 200) { throw new \RuntimeException('Topic render returned HTTP '.$status); }
file_put_contents(base_path('${relativeOutput}'), $response->getContent());
app(\Illuminate\Contracts\Http\Kernel::class)->terminate($request, $response);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);

    const html = await fs.readFile(outputPath, 'utf8');
    const normalizedHtml = html.replace(
        /https?:\/\/[^/"']+\/build\//g,
        `${baseUrl}/build/`,
    );
    await fs.writeFile(outputPath, normalizedHtml);
}

async function startStaticServer() {
    return await new Promise((resolve, reject) => {
        const server = http.createServer(async (request, response) => {
            try {
                const url = new URL(request.url ?? '/', baseUrl);
                let filePath;

                if (url.pathname === topicPath) {
                    filePath = url.searchParams.get('page') === '2' ? pageTwoSnapshot : pageOneSnapshot;
                } else {
                    filePath = resolvePublicPath(url.pathname);
                }

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

function resolvePublicPath(pathname) {
    const candidate = path.resolve(publicDir, `.${decodeURIComponent(pathname)}`);
    const publicPrefix = `${path.resolve(publicDir)}${path.sep}`;

    if (!candidate.startsWith(publicPrefix)) {
        throw new Error(`Refusing path outside public directory: ${pathname}`);
    }

    return candidate;
}

function contentType(filePath) {
    switch (path.extname(filePath).toLowerCase()) {
        case '.html': return 'text/html; charset=utf-8';
        case '.css': return 'text/css; charset=utf-8';
        case '.js': return 'text/javascript; charset=utf-8';
        case '.json': return 'application/json';
        case '.svg': return 'image/svg+xml';
        case '.png': return 'image/png';
        case '.jpg':
        case '.jpeg': return 'image/jpeg';
        case '.webp': return 'image/webp';
        case '.avif': return 'image/avif';
        default: return 'application/octet-stream';
    }
}

async function closeStaticServer(server) {
    if (!server) {
        return;
    }

    await new Promise((resolve) => server.close(resolve));
}

async function runCommand(command, args) {
    await new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            cwd,
            env: {
                ...process.env,
                APP_ENV: 'testing',
                NEWSROOM_PUBLIC_ENABLED: 'true',
                DB_CONNECTION: 'sqlite',
                DB_DATABASE: path.join(cwd, 'database', 'database.sqlite'),
                DB_URL: '',
                CACHE_STORE: 'file',
                SESSION_DRIVER: 'file',
                QUEUE_CONNECTION: 'sync',
                MAIL_MAILER: 'log',
            },
            stdio: 'inherit',
        });

        child.once('error', reject);
        child.once('exit', (code) => {
            if (code === 0) {
                resolve();
                return;
            }

            reject(new Error(`${command} ${args.join(' ')} exited with code ${code}`));
        });
    });
}
