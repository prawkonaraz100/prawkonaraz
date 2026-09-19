import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import http from 'node:http';
import path from 'node:path';
import process from 'node:process';
import { chromium } from 'playwright';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright', 'newsroom-golden-path');
const reportPath = path.join(outputDir, 'report.json');
const heroPath = path.join(outputDir, 'hero.png');
const port = Number(process.env.E2E_NEWSROOM_GOLDEN_PORT ?? '8133');
const baseUrl = `http://127.0.0.1:${port}`;
const title = 'E2E N6 golden path — pełny przepływ redakcyjny';
const slug = 'e2e-n6-golden-path';
const adminEmail = 'newsroom-golden@example.test';
const adminPassword = 'password';

process.env.APP_URL = baseUrl;
process.env.NEWSROOM_PUBLIC_ENABLED = 'true';

const report = {
    started_at: new Date().toISOString(),
    status: 'running',
    base_url: baseUrl,
    article_title: title,
    article_slug: slug,
    steps: [],
};

let browser;
let serverProcess;

try {
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(
        heroPath,
        Buffer.from(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/lKxM9QAAAABJRU5ErkJggg==',
            'base64',
        ),
    );

    console.log('[newsroom-golden] migrate');
    await runCommand('php', ['artisan', 'migrate', '--force']);

    console.log('[newsroom-golden] seed prerequisites');
    const fixture = await seedPrerequisites();

    console.log('[newsroom-golden] start Laravel server');
    serverProcess = startLaravelServer();
    await waitForHttp(`${baseUrl}/admin/login`);

    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 1000 },
        serviceWorkers: 'block',
    });
    const page = await context.newPage();
    page.setDefaultTimeout(15_000);
    page.setDefaultNavigationTimeout(20_000);

    console.log('[newsroom-golden] login');
    await page.goto(`${baseUrl}/admin/login`, { waitUntil: 'domcontentloaded' });
    await page.locator('input[type="email"]').fill(adminEmail);
    await page.locator('input[type="password"]').fill(adminPassword);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/admin/login'), { timeout: 20_000 }),
        page.locator('button[type="submit"]').click(),
    ]);
    report.steps.push('login-admin');

    console.log('[newsroom-golden] create draft through Filament page component');
    await page.goto(`${baseUrl}${fixture.create_path}`, { waitUntil: 'networkidle' });
    assert(
        new URL(page.url()).pathname === fixture.create_path,
        `Create page redirected unexpectedly to ${page.url()}.`,
    );
    await page.locator('form').first().waitFor();

    const uploadInput = page.locator('input[type="file"]').first();
    await uploadInput.waitFor();
    if (await uploadInput.count() !== 1) {
        throw new Error('Hero FileUpload input was not found on the create page.');
    }

    await uploadInput.setInputFiles(heroPath);
    await waitForLivewireIdle(page);

    await setPageComponentState(page, {
        'data.type': 'news',
        'data.category_id': fixture.category_id,
        'data.title': title,
        'data.slug': slug,
        'data.author_id': fixture.author_id,
        'data.origin_type': 'original',
        'data.regulatory_status': 'not_applicable',
        'data.lead': 'Testowy lead integracyjny dla pełnego przepływu redakcyjnego N6-001.',
        'data.body_blocks': [
            {
                type: 'context',
                data: {
                    variant: 'uwaga',
                    title: 'Golden path',
                    text: 'Ten blok został zapisany przez rzeczywisty komponent Filament i jest częścią publicznego artykułu.',
                },
            },
            {
                type: 'question_group',
                data: {
                    question_ids: [fixture.question_id],
                },
            },
            {
                type: 'product_cta',
                data: {
                    kind: 'test',
                },
            },
        ],
        'data.sources': [
            {
                source_type: 'official',
                publisher: 'E2E Instytucja',
                title: 'E2E źródło oficjalne',
                url: 'https://example.test/newsroom-golden-source',
                published_at: null,
                accessed_at: null,
                is_primary: true,
                is_official: true,
                is_publicly_cited: true,
                note: null,
            },
        ],
        'data.topic_ids': [fixture.topic_id],
        'data.question_relations': [
            {
                question_id: fixture.question_id,
                relation_type: 'direct',
                note: 'Relacja golden path.',
            },
        ],
        'data.hero_image_alt': 'Testowy obraz hero golden path N6-001',
        'data.hero_focal_x': 0.35,
        'data.hero_focal_y': 0.65,
        'data.editorial_note': 'N6-001 browser golden path.',
    });

    const createSubmit = page.locator('form button[type="submit"]').first();
    await createSubmit.waitFor();
    await createSubmit.click();
    await page.waitForLoadState('networkidle').catch(() => {});
    await waitForLivewireIdle(page);

    const article = await readCreatedArticle();
    assert(article.workflow_status === 'draft', `Expected draft after create, got ${article.workflow_status}.`);
    assert(article.sources_count === 1, `Expected one source, got ${article.sources_count}.`);
    assert(article.questions_count === 1, `Expected one question relation, got ${article.questions_count}.`);
    assert(article.topics_count === 1, `Expected one topic relation, got ${article.topics_count}.`);
    assert(Boolean(article.hero_image_path), 'Expected persisted hero image path.');
    assert(article.hero_image_width > 0 && article.hero_image_height > 0, 'Expected verified hero dimensions.');
    assert(Math.abs(article.hero_focal_x - 0.35) < 0.001, `Unexpected hero focal X: ${article.hero_focal_x}.`);
    assert(Math.abs(article.hero_focal_y - 0.65) < 0.001, `Unexpected hero focal Y: ${article.hero_focal_y}.`);
    report.steps.push('create-draft-body-source-relations-hero');

    console.log('[newsroom-golden] private preview');
    await page.goto(`${baseUrl}${article.edit_path}`, { waitUntil: 'networkidle' });
    const previewPage = await context.newPage();
    const previewResponse = await previewPage.goto(`${baseUrl}${article.preview_path}`, {
        waitUntil: 'domcontentloaded',
    });

    assert(previewResponse?.status() === 200, `Preview returned HTTP ${previewResponse?.status() ?? 'none'}.`);
    const previewHeaders = previewResponse.headers();
    assert(
        (previewHeaders['cache-control'] ?? '').includes('private')
            && (previewHeaders['cache-control'] ?? '').includes('no-store'),
        `Preview cache contract failed: ${previewHeaders['cache-control'] ?? 'missing'}.`,
    );
    assert(
        (previewHeaders['x-robots-tag'] ?? '').toLowerCase().includes('noindex'),
        `Preview robots contract failed: ${previewHeaders['x-robots-tag'] ?? 'missing'}.`,
    );
    await previewPage.getByRole('heading', { level: 1, name: title }).waitFor();
    const previewText = await previewPage.locator('body').innerText();
    assert(previewText.includes('Golden path'), 'Preview is missing the body block.');
    assert(previewText.includes('E2E źródło oficjalne'), 'Preview is missing the public source.');
    await previewPage.close();
    report.steps.push('private-preview');

    console.log('[newsroom-golden] review workflow');
    await runHeaderAction(page, 'Wyślij do review', 'Artykuł wysłany do review.');
    let workflow = await readCreatedArticle();
    assert(workflow.workflow_status === 'in_review', `Expected in_review, got ${workflow.workflow_status}.`);

    await runHeaderAction(page, 'Oznacz jako sprawdzony', 'Review zakończony.');
    workflow = await readCreatedArticle();
    assert(Boolean(workflow.reviewed_at), 'Expected reviewed_at after markReviewed.');

    await runHeaderAction(page, 'Opublikuj teraz', 'Artykuł został opublikowany.');
    workflow = await readCreatedArticle();
    assert(workflow.workflow_status === 'published', `Expected published, got ${workflow.workflow_status}.`);
    assert(Boolean(workflow.published_at), 'Expected published_at after publish.');
    report.steps.push('submit-review-mark-reviewed-publish');

    console.log('[newsroom-golden] public hub -> article');
    const hubResponse = await page.goto(`${baseUrl}/aktualnosci`, { waitUntil: 'domcontentloaded' });
    assert(hubResponse?.status() === 200, `Newsroom hub returned HTTP ${hubResponse?.status() ?? 'none'}.`);

    const articleLink = page.locator(`a[href="${article.public_path}"]`).first();
    await articleLink.waitFor();
    await Promise.all([
        page.waitForURL((url) => url.pathname === article.public_path),
        articleLink.click(),
    ]);
    await page.getByRole('heading', { level: 1, name: title }).waitFor();

    const bodyText = await page.locator('body').innerText();
    for (const expected of ['Golden path', 'E2E źródło oficjalne', fixture.topic_title]) {
        assert(bodyText.includes(expected), `Public article is missing "${expected}".`);
    }
    report.steps.push('hub-to-article');

    console.log('[newsroom-golden] article -> related question');
    const questionLink = page.locator('a[href*="/pytanie/"]').filter({ hasText: fixture.question_external_id }).first();
    if (await questionLink.count() === 0) {
        throw new Error('Related public question link was not rendered by the article Product Bridge.');
    }

    const questionHref = await questionLink.getAttribute('href');
    assert(Boolean(questionHref), 'Related question link is missing href.');
    const questionResponsePromise = page.waitForResponse((response) => (
        response.request().resourceType() === 'document'
        && new URL(response.url()).pathname.includes('/pytanie/')
    ));
    await questionLink.click();
    const questionResponse = await questionResponsePromise;
    assert(questionResponse.status() === 200, `Related question returned HTTP ${questionResponse.status()}.`);
    const questionBody = await page.locator('body').innerText();
    assert(
        questionBody.includes(fixture.question_external_id) || questionBody.includes(fixture.question_prompt),
        'Related question page does not expose the expected question.',
    );
    report.steps.push('article-to-related-question');

    console.log('[newsroom-golden] article -> product');
    await page.goto(`${baseUrl}${article.public_path}`, { waitUntil: 'domcontentloaded' });
    const productLink = page.getByRole('link', { name: 'Sprawdź się w teście' }).first();
    const productHref = await productLink.getAttribute('href');
    assert(productHref?.endsWith('/testy-na-prawo-jazdy'), `Unexpected product CTA href: ${productHref}.`);

    const productResponsePromise = page.waitForResponse((response) => (
        response.request().resourceType() === 'document'
        && new URL(response.url()).pathname === '/testy-na-prawo-jazdy'
    ));
    await productLink.click();
    const productResponse = await productResponsePromise;
    assert(productResponse.status() === 200, `Product landing returned HTTP ${productResponse.status()}.`);
    assert(new URL(page.url()).pathname === '/testy-na-prawo-jazdy', `Unexpected product landing URL: ${page.url()}.`);
    report.steps.push('article-to-product');

    await page.goto(`${baseUrl}${article.public_path}`, { waitUntil: 'domcontentloaded' });
    await page.screenshot({ path: path.join(outputDir, 'public-article.png'), fullPage: true });

    report.article_id = article.id;
    report.public_path = article.public_path;
    report.preview_path = article.preview_path;
    report.related_question_href = questionHref;
    report.status = 'ok';

    console.log('[newsroom-golden] PASS');
} catch (error) {
    report.status = 'failed';
    report.error = error instanceof Error ? error.message : String(error);
    console.error('[newsroom-golden] FAIL', report.error);
    throw error;
} finally {
    report.finished_at = new Date().toISOString();
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
    await browser?.close().catch(() => {});
    await stopProcess(serverProcess);
}

async function seedPrerequisites() {
    const fixturePath = path.join(outputDir, 'fixture.json');
    const php = String.raw`
$admin = \App\Models\User::factory()->admin()->create([
    'name' => 'Newsroom Golden Admin',
    'email' => 'newsroom-golden@example.test',
    'password' => 'password',
]);
$category = \App\Models\ContentCategory::factory()->create([
    'name' => 'Egzaminy E2E',
    'slug' => 'egzaminy-e2e',
    'is_active' => true,
]);
$author = \App\Models\ContentAuthor::factory()->published()->create([
    'name' => 'E2E Redaktor',
    'slug' => 'e2e-redaktor',
]);
$topic = \App\Models\ContentTopic::factory()->published()->create([
    'title' => 'E2E Golden Topic',
    'slug' => 'e2e-golden-topic',
]);
$licenseCategory = \App\Models\LicenseCategory::factory()->categoryB()->create([
    'sort_order' => 1,
]);
$question = \App\Models\Question::factory()->for($licenseCategory, 'licenseCategory')->create([
    'external_id' => 'N6-GOLDEN-001',
    'prompt' => 'Czy ten testowy kierowca powinien zastosować się do zasad bezpieczeństwa?',
    'requires_primary_media' => false,
    'delivery_issue' => null,
]);
file_put_contents(
    base_path('output/playwright/newsroom-golden-path/fixture.json'),
    json_encode([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'topic_id' => $topic->id,
        'topic_title' => $topic->title,
        'question_id' => $question->id,
        'question_external_id' => $question->external_id,
        'question_prompt' => strip_tags((string) $question->prompt),
        'create_path' => parse_url(
            \App\Filament\Resources\ContentArticles\ContentArticleResource::getUrl('create', panel: 'admin'),
            PHP_URL_PATH,
        ),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);
    return JSON.parse(await fs.readFile(fixturePath, 'utf8'));
}

async function readCreatedArticle() {
    const articlePath = path.join(outputDir, 'article.json');
    const php = String.raw`
$article = \App\Models\ContentArticle::query()
    ->where('slug', 'e2e-n6-golden-path')
    ->withCount(['sources', 'questions', 'topics'])
    ->firstOrFail();
file_put_contents(
    base_path('output/playwright/newsroom-golden-path/article.json'),
    json_encode([
        'id' => $article->id,
        'workflow_status' => $article->workflow_status?->value ?? (string) $article->workflow_status,
        'reviewed_at' => $article->reviewed_at?->toISOString(),
        'published_at' => $article->published_at?->toISOString(),
        'sources_count' => $article->sources_count,
        'questions_count' => $article->questions_count,
        'topics_count' => $article->topics_count,
        'hero_image_path' => $article->hero_image_path,
        'hero_image_width' => (int) $article->hero_image_width,
        'hero_image_height' => (int) $article->hero_image_height,
        'hero_focal_x' => (float) $article->hero_focal_x,
        'hero_focal_y' => (float) $article->hero_focal_y,
        'edit_path' => parse_url(
            \App\Filament\Resources\ContentArticles\ContentArticleResource::getUrl(
                'edit',
                ['record' => $article],
                panel: 'admin',
            ),
            PHP_URL_PATH,
        ),
        'preview_path' => parse_url(route('admin.newsroom.articles.preview', $article), PHP_URL_PATH),
        'public_path' => \App\Support\NewsroomRouteContract::canonicalPath(
            $article->type?->value ?? (string) $article->type,
            (string) $article->slug,
        ),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);
    return JSON.parse(await fs.readFile(articlePath, 'utf8'));
}

async function setPageComponentState(page, state) {
    await page.waitForFunction(() => typeof window.Livewire !== 'undefined');

    for (const [key, value] of Object.entries(state)) {
        await page.evaluate(async ({ property, propertyValue }) => {
            const form = document.querySelector('form');
            const root = form?.closest('[wire\\:id]') ?? document.querySelector('[wire\\:id]');
            const id = root?.getAttribute('wire:id');

            if (!id || !window.Livewire) {
                throw new Error('Unable to resolve the page Livewire component.');
            }

            const component = window.Livewire.find(id);
            await component.$set(property, propertyValue, false);
        }, { property: key, propertyValue: value });
    }
}

async function runHeaderAction(page, label, successText) {
    const action = page.getByRole('button', { name: label, exact: true }).first();
    await action.waitFor();
    await action.click();

    const dialog = page.getByRole('dialog').last();
    await dialog.waitFor();

    const sameLabelButton = dialog.getByRole('button', { name: label, exact: true });
    if (await sameLabelButton.count()) {
        await sameLabelButton.last().click();
    } else {
        const submit = dialog.locator('button[type="submit"]').last();
        if (await submit.count()) {
            await submit.click();
        } else {
            const buttons = dialog.getByRole('button');
            const count = await buttons.count();
            if (count === 0) {
                throw new Error(`No confirmation button found for "${label}".`);
            }
            await buttons.nth(count - 1).click();
        }
    }

    await page.getByText(successText, { exact: false }).waitFor({ timeout: 15_000 });
    await waitForLivewireIdle(page);
}

async function waitForLivewireIdle(page) {
    await page.waitForTimeout(400);
}

function startLaravelServer() {
    const child = spawn(
        'php',
        ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`],
        {
            cwd,
            env: {
                ...process.env,
                APP_URL: baseUrl,
                NEWSROOM_PUBLIC_ENABLED: 'true',
            },
            stdio: ['ignore', 'pipe', 'pipe'],
        },
    );

    child.stdout.on('data', (chunk) => process.stdout.write(`[laravel] ${chunk}`));
    child.stderr.on('data', (chunk) => process.stderr.write(`[laravel] ${chunk}`));

    return child;
}

async function waitForHttp(url) {
    const deadline = Date.now() + 20_000;

    while (Date.now() < deadline) {
        try {
            const status = await new Promise((resolve, reject) => {
                const request = http.get(url, (response) => {
                    response.resume();
                    resolve(response.statusCode ?? 0);
                });
                request.setTimeout(2_000, () => request.destroy(new Error('timeout')));
                request.on('error', reject);
            });

            if (status >= 200 && status < 500) {
                return;
            }
        } catch {
            // Server is still starting.
        }

        await new Promise((resolve) => setTimeout(resolve, 250));
    }

    throw new Error(`Laravel server did not become ready at ${url}.`);
}

async function stopProcess(child) {
    if (!child || child.exitCode !== null) return;

    child.kill('SIGTERM');

    await new Promise((resolve) => {
        const timer = setTimeout(() => {
            child.kill('SIGKILL');
            resolve();
        }, 5_000);

        child.once('exit', () => {
            clearTimeout(timer);
            resolve();
        });
    });
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

function assert(condition, message) {
    if (!condition) {
        throw new Error(message);
    }
}
