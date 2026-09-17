import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import { setTimeout as delay } from 'node:timers/promises';
import { chromium } from 'playwright';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright', 'newsroom-article');
const reportPath = path.join(outputDir, 'report.json');
const slug = 'e2e-newsroom-article';
const articlePath = `/aktualnosci/${slug}`;
const title = 'Długi testowy tytuł artykułu newsroomu sprawdzający poprawne zawijanie na małych ekranach';
const port = process.env.E2E_NEWSROOM_PORT ?? '8127';
const baseUrl = `http://127.0.0.1:${port}`;
const sqliteDatabase = path.join(cwd, 'database', 'database.sqlite');
const laravelServerRouter = path.join(
    cwd,
    'vendor',
    'laravel',
    'framework',
    'src',
    'Illuminate',
    'Foundation',
    'resources',
    'server.php',
);
const viewports = [
    { name: '360', width: 360, height: 800 },
    { name: '390', width: 390, height: 844 },
    { name: '430', width: 430, height: 932 },
    { name: '768', width: 768, height: 1024 },
    { name: '1024', width: 1024, height: 900 },
    { name: '1440', width: 1440, height: 1080 },
];

const report = {
    started_at: new Date().toISOString(),
    article_path: articlePath,
    status: 'running',
    browser_health_status: null,
    viewports: [],
    server_log: [],
};

let browser;
let serverProcess;

try {
    await fs.mkdir(outputDir, { recursive: true });
    console.log('[newsroom-e2e] migrate');
    await runCommand('php', ['artisan', 'migrate', '--force']);
    console.log('[newsroom-e2e] seed');
    await seedArticle();
    for (const viewport of viewports) {
        console.log(`[newsroom-e2e] viewport ${viewport.name}`);
        serverProcess = startServer();
        await waitForHealth(`${baseUrl}/api/v1/health`);
        browser = await chromium.launch({ headless: true });

        const context = await browser.newContext({
            viewport: { width: viewport.width, height: viewport.height },
            serviceWorkers: 'block',
        });
        const page = await context.newPage();
        page.setDefaultTimeout(10_000);
        page.setDefaultNavigationTimeout(15_000);

        const response = await page.goto(`${baseUrl}${articlePath}`, {
            waitUntil: 'domcontentloaded',
            timeout: 15_000,
        });

        if (!response || response.status() !== 200) {
            throw new Error(`Article returned ${response?.status() ?? 'no response'} at ${viewport.name}px.`);
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

        const layout = await page.evaluate(() => ({
            scrollWidth: document.documentElement.scrollWidth,
            viewportWidth: window.innerWidth,
        }));

        if (layout.scrollWidth > layout.viewportWidth) {
            throw new Error(
                `Horizontal overflow at ${viewport.name}px: ${layout.scrollWidth}px > ${layout.viewportWidth}px.`,
            );
        }

        const screenshot = path.join(outputDir, `article-${viewport.name}.png`);
        await page.screenshot({ path: screenshot, fullPage: true, timeout: 15_000 });
        report.viewports.push({
            ...viewport,
            status: 'ok',
            scroll_width: layout.scrollWidth,
            screenshot,
        });

        await context.close();
        await browser.close();
        browser = undefined;
        await terminateServer(serverProcess);
        serverProcess = undefined;
        await delay(250);
    }

    report.status = 'ok';
    console.log('[newsroom-e2e] PASS');
} catch (error) {
    report.status = 'failed';
    report.error = error instanceof Error ? error.message : String(error);
    console.error('[newsroom-e2e] FAIL', report.error);
    printServerLog();
    throw error;
} finally {
    report.finished_at = new Date().toISOString();
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
    await browser?.close().catch(() => {});
    await terminateServer(serverProcess);
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

function startServer() {
    const child = spawn(
        'php',
        ['-S', `127.0.0.1:${port}`, laravelServerRouter],
        {
            cwd: path.join(cwd, 'public'),
            env: {
                ...process.env,
                DB_DATABASE: sqliteDatabase,
                CACHE_STORE: 'array',
                SESSION_DRIVER: 'file',
            },
            stdio: ['ignore', 'pipe', 'pipe'],
            windowsHide: true,
            detached: process.platform !== 'win32',
        },
    );

    child.stdout?.on('data', appendServerLog);
    child.stderr?.on('data', appendServerLog);
    child.once('exit', (code, signal) => {
        appendServerLog(`server exited code=${code} signal=${signal}`);
    });

    return child;
}

async function waitForHealth(url) {
    const deadline = Date.now() + 20_000;
    while (Date.now() < deadline) {
        try {
            const response = await fetch(url, { cache: 'no-store' });
            if (response.ok) return;
        } catch {}
        await delay(250);
    }
    throw new Error(`Server health check timed out: ${url}`);
}

function appendServerLog(chunk) {
    const text = String(chunk).trim();
    if (!text) return;

    report.server_log.push(text);
    report.server_log = report.server_log.slice(-30);
}

function printServerLog() {
    if (report.server_log.length === 0) return;
    console.error('[newsroom-e2e] server log tail:');
    for (const line of report.server_log) console.error(line);
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

function signalServer(child, signal) {
    if (!child || child.exitCode !== null) return;

    try {
        if (process.platform === 'win32') {
            child.kill(signal);
        } else {
            process.kill(-child.pid, signal);
        }
    } catch {
        child.kill(signal);
    }
}

async function terminateServer(child) {
    if (!child || child.exitCode !== null) return;

    signalServer(child, 'SIGTERM');
    await Promise.race([
        new Promise((resolve) => child.once('exit', resolve)),
        delay(1_000),
    ]);

    if (child.exitCode === null) {
        signalServer(child, 'SIGKILL');
        await delay(250);
    }
}
