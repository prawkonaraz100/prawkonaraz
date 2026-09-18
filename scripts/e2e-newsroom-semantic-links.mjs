import fs from 'node:fs/promises';
import http from 'node:http';
import path from 'node:path';
import process from 'node:process';
import { spawn } from 'node:child_process';
import { chromium } from 'playwright';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright', 'newsroom-semantic-links');
const pathsFile = path.join(outputDir, 'paths.json');
const reportPath = path.join(outputDir, 'report.json');
const port = Number(process.env.E2E_NEWSROOM_SEMANTIC_PORT ?? '8132');
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
    status: 'running',
    surfaces: [],
};

let browser;
let staticServer;

try {
    await fs.mkdir(outputDir, { recursive: true });

    console.log('[newsroom-semantic-e2e] migrate');
    await runCommand('php', ['artisan', 'migrate', '--force']);

    console.log('[newsroom-semantic-e2e] seed');
    await seedGraph();

    const paths = JSON.parse(await fs.readFile(pathsFile, 'utf8'));
    const surfaces = [
        {
            key: 'article',
            path: paths.article,
            h1: 'Semantic links browser article',
            expected: [
                'Tematy:',
                'Semantic links browser topic',
                'Pytania egzaminacyjne',
                'Podstawa prawna',
                'Znaki drogowe',
                'Powiązane materiały',
                'Semantic links browser related',
            ],
            hrefs: [paths.question, paths.legal, paths.sign, paths.related],
        },
        {
            key: 'question',
            path: paths.question,
            expected: [
                'Materiały powiązane z tym pytaniem',
                'Semantic links browser article',
            ],
            hrefs: [paths.article],
        },
        {
            key: 'legal',
            path: paths.legal,
            h1: 'Semantic links browser legal page',
            expected: [
                'Materiały powiązane z tym przepisem',
                'Semantic links browser article',
            ],
            hrefs: [paths.article],
        },
        {
            key: 'sign',
            path: paths.sign,
            h1: 'B-99 Semantic links browser sign',
            expected: [
                'Materiały powiązane z tym znakiem',
                'Semantic links browser article',
            ],
            hrefs: [paths.article],
        },
    ];

    console.log('[newsroom-semantic-e2e] render SSR snapshots');
    for (const surface of surfaces) {
        surface.snapshot = path.join(outputDir, `${surface.key}.html`);
        await renderSnapshot(surface.path, surface.snapshot);
    }

    staticServer = await startStaticServer(surfaces);
    browser = await chromium.launch({ headless: true });

    for (const viewport of viewports) {
        for (const surface of surfaces) {
            console.log(`[newsroom-semantic-e2e] ${surface.key} viewport ${viewport.name}`);
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

            const response = await page.goto(`${baseUrl}${surface.path}`, {
                waitUntil: 'domcontentloaded',
                timeout: 15_000,
            });

            if (!response || response.status() !== 200) {
                throw new Error(`${surface.key} returned ${response?.status() ?? 'no response'} at ${viewport.name}px.`);
            }

            const stylesheetResponse = await stylesheetResponsePromise;
            if (!stylesheetResponse.ok()) {
                throw new Error(`Built stylesheet returned HTTP ${stylesheetResponse.status()} for ${surface.key} at ${viewport.name}px.`);
            }

            if (surface.h1) {
                const h1 = page.locator('h1');
                if (await h1.count() !== 1 || !(await h1.innerText()).includes(surface.h1)) {
                    throw new Error(`${surface.key} H1 contract failed at ${viewport.name}px.`);
                }
            }

            const bodyText = await page.locator('body').innerText();
            for (const expected of surface.expected) {
                if (!bodyText.includes(expected)) {
                    throw new Error(`${surface.key} missing "${expected}" at ${viewport.name}px.`);
                }
            }

            for (const href of surface.hrefs) {
                if (await page.locator(`a[href="${href}"]`).count() < 1) {
                    throw new Error(`${surface.key} missing crawlable href ${href} at ${viewport.name}px.`);
                }
            }

            const cdp = await context.newCDPSession(page);
            const metrics = await cdp.send('Page.getLayoutMetrics');
            const scrollWidth = Math.ceil(metrics.cssContentSize.width);
            const viewportWidth = Math.ceil(metrics.cssLayoutViewport.clientWidth);

            if (scrollWidth > viewportWidth) {
                throw new Error(`Horizontal overflow on ${surface.key} at ${viewport.name}px: ${scrollWidth}px > ${viewportWidth}px.`);
            }

            const screenshot = path.join(outputDir, `${surface.key}-${viewport.name}.png`);
            await page.screenshot({ path: screenshot, fullPage: true, timeout: 15_000 });

            report.surfaces.push({
                surface: surface.key,
                viewport: viewport.name,
                status: 'ok',
                scroll_width: scrollWidth,
                screenshot,
            });

            await context.close();
        }
    }

    report.status = 'ok';
    console.log('[newsroom-semantic-e2e] PASS');
} catch (error) {
    report.status = 'failed';
    report.error = error instanceof Error ? error.message : String(error);
    console.error('[newsroom-semantic-e2e] FAIL', report.error);
    throw error;
} finally {
    report.finished_at = new Date().toISOString();
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
    await browser?.close().catch(() => {});
    await closeStaticServer(staticServer);
}

async function seedGraph() {
    const php = String.raw`
$licenseCategory = \App\Models\LicenseCategory::factory()->categoryB()->create();
$question = \App\Models\Question::factory()
    ->for($licenseCategory, 'licenseCategory')
    ->create([
        'external_id' => 'N4008-E2E',
        'prompt' => 'Czy semantic reverse link działa poprawnie?',
    ]);

$legalAct = \App\Models\LegalAct::query()->create([
    'slug' => 'semantic-links-browser-act',
    'title' => 'Semantic links browser act',
    'short_title' => 'SLBA',
    'source_url' => 'https://example.test/semantic-act',
    'status' => \App\Models\LegalAct::STATUS_VERIFIED,
]);
$legalUnit = \App\Models\LegalUnit::query()->create([
    'legal_act_id' => $legalAct->id,
    'type' => 'article',
    'label' => 'art. 99',
    'slug' => 'semantic-links-browser-unit',
    'title' => 'Semantic links browser unit',
    'summary' => 'Zweryfikowana jednostka prawna do testu reverse links.',
    'source_url' => 'https://example.test/semantic-act/art-99',
    'status' => \App\Models\LegalUnit::STATUS_VERIFIED,
]);
$legalTopic = \App\Models\LegalTopic::query()->create([
    'slug' => 'semantic-links-browser-legal-topic',
    'title' => 'Semantic links browser legal topic',
    'status' => \App\Models\LegalTopic::STATUS_PUBLISHED,
    'published_at' => now()->subDay(),
]);
$legalPage = \App\Models\LegalContentPage::query()->create([
    'legal_topic_id' => $legalTopic->id,
    'slug' => 'semantic-links-browser-legal',
    'title' => 'Semantic links browser legal page',
    'summary' => 'Publiczna strona przepisu używana przez semantic-link browser QA.',
    'published_at' => now()->subDay(),
    'status' => \App\Models\LegalContentPage::STATUS_PUBLISHED,
]);
$legalPage->legalUnits()->attach($legalUnit->id, [
    'relation_type' => 'direct_basis',
    'sort_order' => 0,
]);

$signAuthor = \App\Models\ContentAuthor::factory()->published()->create([
    'name' => 'Semantic Sign Author',
    'slug' => 'semantic-sign-author',
]);
$signCategory = \App\Models\TrafficSignCategory::factory()->published()->create([
    'name' => 'Semantic sign category',
    'slug' => 'semantic-sign-category',
]);
$sign = \App\Models\TrafficSign::factory()->published()->create([
    'content_author_id' => $signAuthor->id,
    'traffic_sign_category_id' => $signCategory->id,
    'code' => 'B-99',
    'slug' => 'b-99-semantic-links-browser',
    'name' => 'Semantic links browser sign',
]);

$articleCategory = \App\Models\ContentCategory::factory()->create([
    'name' => 'Semantic browser category',
    'slug' => 'semantic-browser-category',
]);
$topic = \App\Models\ContentTopic::factory()->published()->create([
    'title' => 'Semantic links browser topic',
    'slug' => 'semantic-links-browser-topic',
    'description' => 'Jawny topic do browser QA semantic silo.',
]);

$body = \App\Support\NewsroomBodyContract::normalize([
    [
        'type' => \App\Support\NewsroomBodyContract::BLOCK_RICH_TEXT,
        'data' => [
            'content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [[
                        'type' => 'text',
                        'text' => 'Semantic links browser body.',
                    ]],
                ]],
            ],
        ],
    ],
    [
        'type' => \App\Support\NewsroomBodyContract::BLOCK_QUESTION_GROUP,
        'data' => ['question_ids' => [$question->id]],
    ],
    [
        'type' => \App\Support\NewsroomBodyContract::BLOCK_LEGAL_REFERENCE,
        'data' => ['legal_unit_id' => $legalUnit->id],
    ],
    [
        'type' => \App\Support\NewsroomBodyContract::BLOCK_TRAFFIC_SIGN_GROUP,
        'data' => ['traffic_sign_ids' => [$sign->id]],
    ],
]);

$article = \App\Models\ContentArticle::factory()->published()->create([
    'category_id' => $articleCategory->id,
    'title' => 'Semantic links browser article',
    'slug' => 'semantic-links-browser-article',
    'lead' => 'Jawny artykuł do browser QA grafu linkowania.',
    'body_blocks' => $body,
    'editorial_priority' => 100,
]);
$related = \App\Models\ContentArticle::factory()->published()->create([
    'category_id' => $articleCategory->id,
    'title' => 'Semantic links browser related',
    'slug' => 'semantic-links-browser-related',
    'lead' => 'Drugi jawnie semantycznie powiązany materiał.',
    'editorial_priority' => 50,
]);

$topic->articles()->attach([$article->id, $related->id]);
$article->questions()->attach($question->id, [
    'relation_type' => 'direct',
    'sort_order' => 0,
    'note' => null,
]);
$article->legalUnits()->attach($legalUnit->id, [
    'relation_type' => 'direct_basis',
    'sort_order' => 0,
    'note' => null,
]);
$article->trafficSigns()->attach($sign->id, [
    'relation_type' => 'direct',
    'sort_order' => 0,
]);

$questionUrl = app(\App\Support\PublicQuestionCatalogService::class)->questionUrl($question);
$paths = [
    'article' => \App\Support\NewsroomRouteContract::canonicalPath('news', $article->slug),
    'related' => \App\Support\NewsroomRouteContract::canonicalPath('news', $related->slug),
    'question' => parse_url($questionUrl, PHP_URL_PATH),
    'legal' => route('public.regulations.show', $legalPage->slug, false),
    'sign' => route('traffic-signs.show', $sign->slug, false),
];

file_put_contents(
    base_path('output/playwright/newsroom-semantic-links/paths.json'),
    json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);
}

async function renderSnapshot(requestPath, outputPath) {
    const relativeOutput = path.relative(cwd, outputPath).replaceAll('\\', '/');
    const php = String.raw`
$request = \Illuminate\Http\Request::create('${baseUrl}${requestPath}', 'GET');
$response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
$status = $response->getStatusCode();
if ($status !== 200) { throw new \RuntimeException('Semantic render returned HTTP '.$status.' for ${requestPath}'); }
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

async function startStaticServer(surfaces) {
    const byPath = new Map(surfaces.map((surface) => [surface.path, surface.snapshot]));

    return await new Promise((resolve, reject) => {
        const server = http.createServer(async (request, response) => {
            try {
                const url = new URL(request.url ?? '/', baseUrl);
                const filePath = byPath.get(url.pathname) ?? resolvePublicPath(url.pathname);
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
        const child = spawn(command, args, {
            cwd,
            env: process.env,
            stdio: 'inherit',
        });

        child.once('error', reject);
        child.once('exit', (code) => {
            if (code === 0) resolve();
            else reject(new Error(`${command} ${args.join(' ')} exited with code ${code}.`));
        });
    });
}
