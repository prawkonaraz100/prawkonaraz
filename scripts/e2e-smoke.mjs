import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import { setTimeout as delay } from 'node:timers/promises';
import { chromium } from 'playwright';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright');
const successScreenshotPath = path.join(outputDir, 'e2e-smoke-success.png');
const errorScreenshotPath = path.join(outputDir, 'e2e-smoke-error.png');
const reportPath = path.join(outputDir, 'e2e-smoke-report.json');
const port = process.env.E2E_SMOKE_PORT ?? '8126';
const externalBaseUrl = process.env.E2E_SMOKE_BASE_URL?.replace(/\/$/, '');
const baseUrl = externalBaseUrl ?? `http://127.0.0.1:${port}`;
const expectPwaCacheHeaders = process.env.E2E_SMOKE_EXPECT_PWA_CACHE_HEADERS === 'true';
const smokeEmail = process.env.E2E_SMOKE_EMAIL ?? 'smoke-e2e@example.test';
const smokePassword = process.env.E2E_SMOKE_PASSWORD ?? 'ChangeMe123!';
const mobileViewports = [
    { name: '360', width: 360, height: 800 },
    { name: '390', width: 390, height: 844 },
    { name: '430', width: 430, height: 932 },
];
const privatePathPrefixes = [
    '/api/',
    '/auth/',
    '/sanctum/',
    '/login',
    '/logout',
    '/dashboard',
    '/profile',
    '/checkout',
    '/nauka',
    '/study-sessions',
    '/trener-pamieci',
    '/ranking',
    '/admin',
    '/moderator',
];
const smokeEnvironment = {
    ...process.env,
    CACHE_STORE: process.env.E2E_SMOKE_CACHE_STORE ?? 'array',
    SESSION_DRIVER: process.env.E2E_SMOKE_SESSION_DRIVER ?? 'file',
};

const report = {
    started_at: new Date().toISOString(),
    base_url: baseUrl,
    status: 'running',
    steps: [],
    media: {
        image_seen: false,
        video_seen: false,
    },
    mobile: {
        viewports: [],
    },
    pwa: {
        manifest: null,
        service_worker: null,
    },
    artifacts: {
        success_screenshot: successScreenshotPath,
        error_screenshot: errorScreenshotPath,
        report: reportPath,
        mobile_screenshots: Object.fromEntries(
            mobileViewports.map((viewport) => [
                viewport.name,
                path.join(outputDir, `e2e-smoke-mobile-${viewport.name}.png`),
            ]),
        ),
    },
};

let browser;
let page;
let serverProcess;

const phpBin = await resolvePhpBinary();

async function main() {
    await fs.mkdir(outputDir, { recursive: true });

    try {
        await runStep('prepare-build', async () => {
            const manifestPath = path.join(cwd, 'public', 'build', 'manifest.json');

            if (externalBaseUrl) {
                await runCommand(npmExecutable(), ['run', 'build'], {
                    stepName: 'npm-build',
                });

                return;
            }

            try {
                await fs.access(manifestPath);
            } catch {
                await runCommand(npmExecutable(), ['run', 'build'], {
                    stepName: 'npm-build',
                });
            }
        });

        await runStep('bootstrap-environment', async () => {
            await runCommand(phpBin, ['artisan', 'migrate', '--force'], {
                stepName: 'artisan-migrate',
            });
            await runCommand(phpBin, ['artisan', 'storage:link'], {
                stepName: 'artisan-storage-link',
            });
            await runCommand(phpBin, ['artisan', 'ops:seed-smoke-data'], {
                stepName: 'artisan-seed-smoke',
            });
            await runCommand(
                phpBin,
                [
                    'artisan',
                    'app:make-admin',
                    smokeEmail,
                    '--name=Smoke E2E',
                    `--password=${smokePassword}`,
                ],
                {
                    stepName: 'artisan-make-admin',
                },
            );
        });

        await runStep('start-server', async () => {
            if (externalBaseUrl) {
                await waitForHealth(`${baseUrl}/api/v1/health`, 20_000);
                return;
            }

            serverProcess = spawn(
                phpBin,
                ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`],
                {
                    cwd,
                    env: smokeEnvironment,
                    stdio: ['ignore', 'pipe', 'pipe'],
                    windowsHide: true,
                },
            );

            serverProcess.stdout?.on('data', (chunk) => {
                appendServerLog(chunk);
            });
            serverProcess.stderr?.on('data', (chunk) => {
                appendServerLog(chunk);
            });

            serverProcess.once('exit', (code) => {
                if (report.status === 'running') {
                    report.server_exit_code = code;
                }
            });

            await waitForHealth(`${baseUrl}/api/v1/health`, 20_000);
        });

        await runStep('ensure-browser', async () => {
            await ensureChromiumInstalled();
        });

        await runStep('browser-smoke', async () => {
            browser = await chromium.launch({
                headless: process.env.E2E_SMOKE_HEADLESS !== 'false',
            });

            page = await browser.newPage({
                viewport: { width: 1440, height: 1080 },
            });

            await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
            await page.locator('[data-testid="login-email"]').waitFor();
            await page.locator('[data-testid="login-email"]').fill(smokeEmail);
            await page.locator('[data-testid="login-password"]').fill(smokePassword);

            await Promise.all([
                waitForPathname(page, /^(?:\/moje-postepy|\/nauka)$/),
                page.locator('[data-testid="login-submit"]').click(),
            ]);

            await page.locator('[aria-label="Panel nauki"]').waitFor();
            await startSmokeSession(page);

            await page.locator('[data-testid="session-current-question"]').waitFor();

            if ((await page.locator('[data-testid="session-question-image"]').count()) > 0) {
                report.media.image_seen = true;
            }

            if ((await page.locator('[data-testid="session-question-video"]').count()) > 0) {
                report.media.video_seen = true;
            }

            const prompt = (
                await page.locator('[data-testid="session-question-prompt"]').textContent()
            )?.trim();

            if (!prompt) {
                throw new Error('Current question prompt is missing.');
            }

            await page.locator('[data-testid="answer-option-a"]').click();

            await page.waitForFunction(
                (previousPrompt) => {
                    const completed = document.querySelector(
                        '[data-testid="session-complete"]',
                    );

                    if (completed) {
                        return true;
                    }

                    const promptElement = document.querySelector(
                        '[data-testid="session-question-prompt"]',
                    );

                    return (
                        promptElement?.textContent?.trim() !== undefined &&
                        promptElement.textContent.trim() !== previousPrompt
                    );
                },
                prompt,
                {
                    timeout: 10_000,
                },
            );

            await page.goto(`${baseUrl}/nauka`, { waitUntil: 'networkidle' });
            await page.locator('[aria-label="Panel nauki"]').waitFor();

            await runMobilePwaChecks(page);

            await page.screenshot({
                path: successScreenshotPath,
                fullPage: true,
            });
        });

        report.status = 'ok';
    } catch (error) {
        report.status = 'failed';
        report.error = serializeError(error);

        if (page) {
            try {
                await page.screenshot({
                    path: errorScreenshotPath,
                    fullPage: true,
                });
            } catch {
                // Ignore screenshot failures while handling the root error.
            }
        }

        throw error;
    } finally {
        report.finished_at = new Date().toISOString();
        await writeReport();
        await browser?.close().catch(() => {});
        await terminateServer(serverProcess);
    }
}

await main().catch((error) => {
    console.error(error instanceof Error ? error.message : String(error));
    process.exitCode = 1;
});

async function resolvePhpBinary() {
    if (process.env.PHP_BIN) {
        return process.env.PHP_BIN;
    }

    const localPhp = path.join(cwd, '.tools', 'php83', 'php.exe');

    try {
        await fs.access(localPhp);

        return localPhp;
    } catch {
        return 'php';
    }
}

async function ensureChromiumInstalled() {
    try {
        const probe = await chromium.launch({ headless: true });
        await probe.close();
    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);

        if (!message.includes('Executable')) {
            throw error;
        }

        await runCommand(npxExecutable(), ['playwright', 'install', 'chromium'], {
            stepName: 'playwright-install-chromium',
        });
    }
}

async function startSmokeSession(pageHandle) {
    const startButton = pageHandle
        .locator('[aria-label="Twoja aktualna sesja"] button')
        .first();

    await startButton.waitFor();
    await startButton.click();

    const replaceSessionDialog = pageHandle.getByRole('dialog');

    if (await replaceSessionDialog.isVisible()) {
        await Promise.all([
            waitForPathname(pageHandle, '/nauka/teraz'),
            replaceSessionDialog.getByRole('button', { name: 'Rozpocznij nową' }).click(),
        ]);

        return;
    }

    await waitForPathname(pageHandle, '/nauka/teraz');
}

async function runMobilePwaChecks(pageHandle) {
    await runStep('pwa-http-contract', async () => {
        const manifestResponse = await fetch(`${baseUrl}/manifest.webmanifest`);

        if (!manifestResponse.ok) {
            throw new Error(`PWA manifest returned ${manifestResponse.status}.`);
        }

        const manifest = await manifestResponse.json();

        if (
            manifest.display !== 'standalone'
            || manifest.start_url !== '/nauka?utm_source=pwa'
            || !Array.isArray(manifest.icons)
            || manifest.icons.length === 0
        ) {
            throw new Error('PWA manifest is missing its installability contract.');
        }

        const workerResponse = await fetch(`${baseUrl}/service-worker.js`);
        const workerCacheControl = workerResponse.headers.get('cache-control') ?? '';

        if (
            !workerResponse.ok
            || (expectPwaCacheHeaders && !workerCacheControl.includes('no-store'))
        ) {
            throw new Error('Service worker must be served with no-store cache policy.');
        }

        report.pwa.manifest = {
            display: manifest.display,
            start_url: manifest.start_url,
            icon_count: manifest.icons.length,
            service_worker_cache_control: workerCacheControl,
            cache_policy_verified: expectPwaCacheHeaders,
        };
    });

    await pageHandle.goto(`${baseUrl}/nauka`, { waitUntil: 'networkidle' });
    await pageHandle.locator('[aria-label="Panel nauki"]').waitFor();

    await runStep('pwa-service-worker', async () => {
        await pageHandle.waitForFunction(
            () => navigator.serviceWorker?.controller !== null,
            null,
            { timeout: 10_000 },
        );

        const registration = await pageHandle.evaluate(async () => {
            const readyRegistration = await navigator.serviceWorker.ready;

            return {
                controlled: navigator.serviceWorker.controller !== null,
                scope: readyRegistration.scope,
            };
        });

        const healthRequestOk = await pageHandle.evaluate(async () => {
            const response = await fetch('/api/v1/health', {
                cache: 'no-store',
                credentials: 'same-origin',
            });

            return response.ok;
        });

        if (!healthRequestOk) {
            throw new Error('API health request did not succeed through the service worker.');
        }

        const cachedPrivatePaths = await pageHandle.evaluate(async (privatePrefixes) => {
            const privatePaths = [];

            for (const cacheName of await caches.keys()) {
                const cache = await caches.open(cacheName);

                for (const request of await cache.keys()) {
                    const pathname = new URL(request.url).pathname;

                    if (privatePrefixes.some((prefix) => pathname.startsWith(prefix))) {
                        privatePaths.push(pathname);
                    }
                }
            }

            return privatePaths;
        }, privatePathPrefixes);

        if (cachedPrivatePaths.length > 0) {
            throw new Error(
                `Service worker cached private paths: ${cachedPrivatePaths.join(', ')}.`,
            );
        }

        report.pwa.service_worker = {
            ...registration,
            cached_private_paths: cachedPrivatePaths,
            offline_fallback: false,
        };
    });

    await runStep('pwa-offline-fallback', async () => {
        const context = pageHandle.context();

        await context.setOffline(true);

        try {
            await pageHandle.goto(`${baseUrl}/nauka?e2e=offline`, {
                waitUntil: 'domcontentloaded',
                timeout: 15_000,
            });
            await pageHandle.getByRole('heading', { name: 'Brak polaczenia' }).waitFor();
            report.pwa.service_worker.offline_fallback = true;
        } finally {
            await context.setOffline(false);
        }

        await pageHandle.goto(`${baseUrl}/nauka`, { waitUntil: 'networkidle' });
        await pageHandle.locator('[aria-label="Panel nauki"]').waitFor();
    });

    await runStep('mobile-viewport-matrix', async () => {
        for (const viewport of mobileViewports) {
            await pageHandle.setViewportSize({
                width: viewport.width,
                height: viewport.height,
            });
            await pageHandle.goto(`${baseUrl}/nauka`, { waitUntil: 'networkidle' });

            const panel = pageHandle.locator('[aria-label="Mobilny panel nauki"]');
            const navigation = pageHandle.locator('[aria-label="Nawigacja aplikacji"]');

            await panel.waitFor();
            await navigation.waitFor();

            const layout = await pageHandle.evaluate(() => ({
                scroll_width: document.documentElement.scrollWidth,
                viewport_width: window.innerWidth,
            }));

            if (layout.scroll_width > layout.viewport_width) {
                throw new Error(
                    `Mobile layout overflows at ${viewport.width}px: ${layout.scroll_width}px content width.`,
                );
            }

            const navigationItems = await navigation.locator('a').evaluateAll((items) => (
                items.map((item) => ({
                    href: new URL(item.href).pathname,
                    label: item.textContent?.trim() ?? '',
                }))
            ));
            const expectedPaths = [
                '/nauka',
                '/trener-pamieci',
                '/nauka/teraz',
                '/nauka/znaki-drogowe',
                '/nauka/ranking',
                '/profile',
            ];

            if (
                navigationItems.length !== expectedPaths.length
                || navigationItems.some((item, index) => item.href !== expectedPaths[index])
            ) {
                throw new Error(`Bottom navigation contract failed at ${viewport.width}px.`);
            }

            const itemBoxes = await navigation.locator('a').evaluateAll((items) => (
                items.map((item) => {
                    const rect = item.getBoundingClientRect();

                    return {
                        left: rect.left,
                        right: rect.right,
                        top: rect.top,
                        bottom: rect.bottom,
                    };
                })
            ));

            if (
                itemBoxes.some((item) => (
                    item.left < 0
                    || item.right > viewport.width
                    || item.top < 0
                    || item.bottom > viewport.height
                ))
            ) {
                throw new Error(`Bottom navigation is outside the viewport at ${viewport.width}px.`);
            }

            const progressLink = panel.getByRole('link', {
                name: 'Zobacz szczegółowy postęp kursu',
            });
            const progressHref = await progressLink.getAttribute('href');

            if (!progressHref || !/^\/analytics\/categories\/\d+$/.test(new URL(progressHref, baseUrl).pathname)) {
                throw new Error(`Mobile progress link contract failed at ${viewport.width}px.`);
            }

            await progressLink.click();
            await pageHandle.getByRole('heading', { name: 'Postęp', exact: true }).waitFor();
            await pageHandle.getByRole('heading', { name: 'Ostatnie 7 dni', exact: true }).waitFor();

            const analyticsLayout = await pageHandle.evaluate(() => ({
                scroll_width: document.documentElement.scrollWidth,
                viewport_width: window.innerWidth,
            }));

            if (analyticsLayout.scroll_width > analyticsLayout.viewport_width) {
                throw new Error(
                    `Mobile analytics overflows at ${viewport.width}px: ${analyticsLayout.scroll_width}px content width.`,
                );
            }

            await pageHandle.goto(`${baseUrl}/nauka`, { waitUntil: 'networkidle' });
            await panel.waitFor();

            await panel.getByRole('button', { name: /Zen mode/ }).click();

            const setupCta = pageHandle
                .getByRole('dialog')
                .getByRole('button', { name: 'Rozpocznij naukę', exact: true });

            await setupCta.scrollIntoViewIfNeeded();
            await setupCta.click();
            await pageHandle
                .getByRole('dialog')
                .getByRole('heading', { name: 'Masz aktywną sesję', exact: true })
                .waitFor();

            const screenshotPath = report.artifacts.mobile_screenshots[viewport.name];

            await pageHandle.screenshot({
                path: screenshotPath,
                fullPage: true,
            });

            report.mobile.viewports.push({
                ...viewport,
                navigation_items: navigationItems,
                setup_cta_clicked: true,
                screenshot: screenshotPath,
            });
        }
    });
}

async function waitForHealth(url, timeoutMs) {
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeoutMs) {
        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (response.ok) {
                return;
            }
        } catch {
            // Server is still warming up.
        }

        await delay(500);
    }

    throw new Error(`Health endpoint did not become ready within ${timeoutMs}ms.`);
}

async function waitForPathname(pageHandle, matcher, timeout = 15_000) {
    await pageHandle.waitForFunction(
        (expected) => {
            if (typeof expected === 'string') {
                return window.location.pathname === expected;
            }

            return new RegExp(expected.pattern, expected.flags).test(
                window.location.pathname,
            );
        },
        typeof matcher === 'string'
            ? matcher
            : {
                  pattern: matcher.source,
                  flags: matcher.flags,
              },
        { timeout },
    );
}

async function runCommand(command, args, { stepName }) {
    const startedAt = Date.now();
    const usesWindowsCommandScript = process.platform === 'win32' && command.endsWith('.cmd');
    const executable = usesWindowsCommandScript
        ? (process.env.ComSpec ?? 'cmd.exe')
        : command;
    const executableArgs = usesWindowsCommandScript
        ? ['/d', '/s', '/c', command, ...args]
        : args;

    return new Promise((resolve, reject) => {
        const child = spawn(executable, executableArgs, {
            cwd,
            env: smokeEnvironment,
            stdio: ['ignore', 'pipe', 'pipe'],
            windowsHide: true,
        });

        let stdout = '';
        let stderr = '';

        child.stdout?.on('data', (chunk) => {
            stdout += chunk.toString();
        });

        child.stderr?.on('data', (chunk) => {
            stderr += chunk.toString();
        });

        child.once('error', (error) => {
            reject(error);
        });

        child.once('exit', (code) => {
            const durationMs = Date.now() - startedAt;
            report.steps.push({
                name: stepName,
                type: 'command',
                command: [command, ...args].join(' '),
                duration_ms: durationMs,
                exit_code: code,
            });

            if (code === 0) {
                resolve({
                    stdout,
                    stderr,
                });

                return;
            }

            reject(
                new Error(
                    `Command failed (${code}): ${command} ${args.join(' ')}\n${stdout}\n${stderr}`.trim(),
                ),
            );
        });
    });
}

async function runStep(name, callback) {
    const startedAt = Date.now();

    try {
        const result = await callback();

        report.steps.push({
            name,
            type: 'step',
            status: 'ok',
            duration_ms: Date.now() - startedAt,
        });

        return result;
    } catch (error) {
        report.steps.push({
            name,
            type: 'step',
            status: 'failed',
            duration_ms: Date.now() - startedAt,
            error: serializeError(error),
        });

        throw error;
    }
}

function appendServerLog(chunk) {
    const text = chunk.toString().trim();

    if (text === '') {
        return;
    }

    report.server_log ??= [];
    report.server_log.push(text);
    report.server_log = report.server_log.slice(-20);
}

async function writeReport() {
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`, 'utf8');
}

async function terminateServer(child) {
    if (!child || child.exitCode !== null) {
        return;
    }

    if (process.platform === 'win32') {
        await new Promise((resolve) => {
            const killer = spawn('taskkill', ['/pid', String(child.pid), '/t', '/f'], {
                cwd,
                env: process.env,
                stdio: 'ignore',
                windowsHide: true,
            });

            killer.once('exit', () => resolve());
            killer.once('error', () => resolve());
        });

        return;
    }

    child.kill('SIGTERM');
    await delay(500);
}

function npmExecutable() {
    return process.platform === 'win32' ? 'npm.cmd' : 'npm';
}

function npxExecutable() {
    return process.platform === 'win32' ? 'npx.cmd' : 'npx';
}

function serializeError(error) {
    if (error instanceof Error) {
        return {
            name: error.name,
            message: error.message,
            stack: error.stack,
        };
    }

    return {
        message: String(error),
    };
}
