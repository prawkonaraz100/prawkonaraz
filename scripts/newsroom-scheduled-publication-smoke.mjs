import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import fs from 'node:fs/promises';
import http from 'node:http';
import path from 'node:path';
import process from 'node:process';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'newsroom-scheduled-publication-smoke');
const fixturePath = path.join(outputDir, 'fixture.json');
const coordinatorPath = path.join(outputDir, 'coordinator.json');
const articleStatePath = path.join(outputDir, 'article-state.json');
const reportPath = path.join(outputDir, 'report.json');
const port = Number(process.env.N6_SCHEDULED_SMOKE_PORT ?? '8134');
const dueDelaySeconds = Number(process.env.N6_SCHEDULED_SMOKE_DELAY_SECONDS ?? '45');
const baseUrl = `http://127.0.0.1:${port}`;
const runToken = String(process.env.GITHUB_RUN_ID ?? Date.now()).replace(/[^0-9]/g, '').slice(-12) || 'local';
const slug = `n6-scheduled-smoke-${runToken}`;
const title = `N6 scheduled smoke ${runToken}`;

if (!Number.isInteger(dueDelaySeconds) || dueDelaySeconds < 20 || dueDelaySeconds > 120) {
    throw new Error('N6_SCHEDULED_SMOKE_DELAY_SECONDS must be an integer between 20 and 120.');
}

process.env.NEWSROOM_PUBLIC_ENABLED = 'true';
process.env.N6_SCHEDULED_SMOKE_SLUG = slug;
process.env.N6_SCHEDULED_SMOKE_TITLE = title;
process.env.N6_SCHEDULED_SMOKE_DELAY_SECONDS = String(dueDelaySeconds);

const report = {
    started_at: new Date().toISOString(),
    status: 'running',
    origin_url: baseUrl,
    canonical_app_url: process.env.APP_URL ?? null,
    slug,
    due_delay_seconds: dueDelaySeconds,
    steps: [],
};

let serverProcess;

try {
    await fs.mkdir(outputDir, { recursive: true });

    console.log('[n6-scheduled] seed baseline + scheduled fixture using real clock');
    await seedFixture();
    const fixture = JSON.parse(await fs.readFile(fixturePath, 'utf8'));

    const scheduledState = await readArticleState();
    assert(scheduledState.workflow_status === 'scheduled', `Expected scheduled fixture, got ${scheduledState.workflow_status}.`);
    assert(scheduledState.first_published_at === null, 'Scheduled fixture must not have first_published_at before due publication.');
    report.steps.push('seed-scheduled-fixture');

    const dirtyAfterSchedule = await readCoordinatorState();
    assert(dirtyAfterSchedule.dirty === true, 'Scheduling must leave SEO artifacts dirty before the first refresh.');
    assert(dirtyAfterSchedule.current_version > dirtyAfterSchedule.clean_version, 'Dirty version must be ahead of clean version after scheduling.');
    report.coordinator_after_schedule = dirtyAfterSchedule;

    console.log('[n6-scheduled] baseline dirty/version refresh before due time');
    const baselineRefresh = await runCommandCapture('php', ['artisan', 'newsroom:refresh-seo-artifacts-if-dirty']);
    assert(baselineRefresh.code === 0, `Baseline SEO refresh failed: ${baselineRefresh.output}`);

    const cleanBeforeDue = await readCoordinatorState();
    assert(cleanBeforeDue.dirty === false, 'SEO artifacts must be clean after baseline refresh.');
    assert(cleanBeforeDue.current_version === cleanBeforeDue.clean_version, 'Baseline refresh must clean the exact current version.');
    report.coordinator_before_due = cleanBeforeDue;

    const articlesBefore = await readRequiredFile(path.join(cwd, 'public', 'sitemaps', 'articles.xml'));
    const newsBefore = await readRequiredFile(path.join(cwd, 'public', 'sitemaps', 'news.xml'));
    assert(!articlesBefore.includes(slug), 'Scheduled article leaked into articles sitemap before due publication.');
    assert(!newsBefore.includes(slug), 'Scheduled article leaked into Google News sitemap before due publication.');
    await fs.writeFile(path.join(outputDir, 'articles-before.xml'), articlesBefore);
    await fs.writeFile(path.join(outputDir, 'news-before.xml'), newsBefore);
    report.articles_before_sha256 = sha256(articlesBefore);
    report.news_before_sha256 = sha256(newsBefore);

    console.log('[n6-scheduled] start isolated production-mode HTTP server');
    serverProcess = startLaravelServer();
    await waitForHttp(`${baseUrl}/aktualnosci`);

    const beforeArticle = await fetchText(`${baseUrl}${fixture.public_path}`);
    assert(beforeArticle.status === 404, `Scheduled public article must be unavailable before due time; got HTTP ${beforeArticle.status}.`);

    const beforeHub = await fetchText(`${baseUrl}/aktualnosci`);
    assert(beforeHub.status === 200, `Newsroom hub returned HTTP ${beforeHub.status} before due time.`);
    assert(!beforeHub.body.includes(slug) && !beforeHub.body.includes(title), 'Scheduled article leaked into hub before due publication.');

    const beforeFeed = await fetchText(`${baseUrl}/aktualnosci/feed.xml`);
    assert(beforeFeed.status === 200, `Newsroom feed returned HTTP ${beforeFeed.status} before due time.`);
    assert(!beforeFeed.body.includes(slug), 'Scheduled article leaked into feed before due publication.');

    const prematurePublish = await runCommandCapture('php', ['artisan', 'newsroom:publish-due', '--limit=10']);
    assert(prematurePublish.code === 0, `Pre-due publish command failed unexpectedly: ${prematurePublish.output}`);
    assert(prematurePublish.output.includes('published=0 failed=0'), `Pre-due publish command attempted publication: ${prematurePublish.output}`);

    report.steps.push('hidden-before-due');

    const dueAtMs = Date.parse(fixture.scheduled_for);
    const waitMs = Math.max(0, dueAtMs - Date.now() + 1500);
    assert(waitMs <= 130_000, `Unexpected due wait ${waitMs}ms.`);

    console.log(`[n6-scheduled] wait ${waitMs}ms for real due time ${fixture.scheduled_for}`);
    await sleep(waitMs);

    console.log('[n6-scheduled] run real due publication command');
    const publish = await runCommandCapture('php', ['artisan', 'newsroom:publish-due', '--limit=10']);
    assert(publish.code === 0, `Due publish command failed: ${publish.output}`);
    assert(publish.output.includes('published=1 failed=0 skipped=0'), `Due publish did not publish exactly one fixture: ${publish.output}`);

    const publishedState = await readArticleState();
    assert(publishedState.workflow_status === 'published', `Expected published fixture after due command, got ${publishedState.workflow_status}.`);
    assert(Boolean(publishedState.first_published_at), 'Published fixture is missing first_published_at.');
    assert(publishedState.scheduled_for === null, 'Published fixture must clear scheduled_for.');
    assert(publishedState.publish_trigger === 'scheduler', `Expected scheduler audit trigger, got ${publishedState.publish_trigger}.`);
    report.article_after_publish = publishedState;

    const dirtyAfterPublish = await readCoordinatorState();
    assert(dirtyAfterPublish.dirty === true, 'Due publication must leave SEO artifacts dirty.');
    assert(dirtyAfterPublish.current_version > cleanBeforeDue.clean_version, 'Due publication must advance the SEO artifact version.');
    report.coordinator_after_publish = dirtyAfterPublish;

    const visibleBeforeRefresh = await fetchText(`${baseUrl}${fixture.public_path}`);
    assert(visibleBeforeRefresh.status === 200, `Published article must be publicly visible immediately; got HTTP ${visibleBeforeRefresh.status}.`);
    report.steps.push('published-by-scheduler');

    console.log('[n6-scheduled] refresh dirty SEO artifacts without daily cron');
    const refresh = await runCommandCapture('php', ['artisan', 'newsroom:refresh-seo-artifacts-if-dirty']);
    assert(refresh.code === 0, `Post-publish SEO refresh failed: ${refresh.output}`);

    const cleanAfterPublish = await readCoordinatorState();
    assert(cleanAfterPublish.dirty === false, 'SEO artifacts must be clean after scheduled post-publish refresh.');
    assert(cleanAfterPublish.current_version === cleanAfterPublish.clean_version, 'Post-publish refresh must clean the latest version.');
    assert(cleanAfterPublish.clean_version > cleanBeforeDue.clean_version, 'Post-publish clean version must advance beyond the pre-due clean version.');
    report.coordinator_after_refresh = cleanAfterPublish;

    const afterArticle = await fetchText(`${baseUrl}${fixture.public_path}`);
    assert(afterArticle.status === 200, `Published article returned HTTP ${afterArticle.status} after refresh.`);
    assert(afterArticle.body.includes(title), 'Published article response is missing the smoke title.');

    const afterHub = await fetchText(`${baseUrl}/aktualnosci`);
    assert(afterHub.status === 200, `Newsroom hub returned HTTP ${afterHub.status} after publication.`);
    assert(afterHub.body.includes(slug) || afterHub.body.includes(title), 'Published article is missing from the newsroom hub.');

    const afterFeed = await fetchText(`${baseUrl}/aktualnosci/feed.xml`);
    assert(afterFeed.status === 200, `Newsroom feed returned HTTP ${afterFeed.status} after publication.`);
    assert(afterFeed.body.includes(slug), 'Published scheduled article is missing from Atom feed.');
    await fs.writeFile(path.join(outputDir, 'feed-after.xml'), afterFeed.body);

    const articlesAfter = await readRequiredFile(path.join(cwd, 'public', 'sitemaps', 'articles.xml'));
    const newsAfter = await readRequiredFile(path.join(cwd, 'public', 'sitemaps', 'news.xml'));
    assert(articlesAfter.includes(slug), 'Published scheduled article is missing from articles sitemap.');
    assert(newsAfter.includes(slug), 'Published scheduled article is missing from Google News sitemap.');

    const articlesAfterHash = sha256(articlesAfter);
    const newsAfterHash = sha256(newsAfter);
    assert(articlesAfterHash !== report.articles_before_sha256, 'Articles sitemap hash did not change after scheduled publication.');
    assert(newsAfterHash !== report.news_before_sha256, 'Google News sitemap hash did not change after scheduled publication.');

    await fs.writeFile(path.join(outputDir, 'articles-after.xml'), articlesAfter);
    await fs.writeFile(path.join(outputDir, 'news-after.xml'), newsAfter);
    report.articles_after_sha256 = articlesAfterHash;
    report.news_after_sha256 = newsAfterHash;
    report.steps.push('dirty-refresh-feed-sitemap-fresh');

    report.status = 'ok';
    console.log('[n6-scheduled] PASS');
} catch (error) {
    report.status = 'failed';
    report.error = error instanceof Error ? error.message : String(error);
    console.error('[n6-scheduled] FAIL', report.error);
    process.exitCode = 1;
} finally {
    report.finished_at = new Date().toISOString();
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
    await stopProcess(serverProcess);
}

async function seedFixture() {
    const php = String.raw`
$slug = (string) env('N6_SCHEDULED_SMOKE_SLUG');
$title = (string) env('N6_SCHEDULED_SMOKE_TITLE');
$delay = max(20, (int) env('N6_SCHEDULED_SMOKE_DELAY_SECONDS', 45));

$category = \App\Models\ContentCategory::factory()->create([
    'name' => 'N6 Scheduled Smoke',
    'slug' => 'n6-scheduled-smoke',
    'is_active' => true,
]);

$author = \App\Models\ContentAuthor::factory()->published()->create([
    'name' => 'N6 Smoke Author',
    'slug' => 'n6-smoke-author',
]);

\App\Models\ContentArticle::factory()->published()->create([
    'category_id' => $category->id,
    'author_id' => $author->id,
    'title' => 'N6 baseline published news',
    'slug' => 'n6-baseline-published-news',
    'first_published_at' => now()->subMinutes(10),
    'published_at' => now()->subMinutes(10),
    'public_state_changed_at' => now()->subMinutes(10),
]);

$article = \App\Models\ContentArticle::factory()->inReview()->create([
    'category_id' => $category->id,
    'author_id' => $author->id,
    'title' => $title,
    'slug' => $slug,
    'lead' => 'Scheduled publication release smoke using the real scheduler clock.',
]);

\App\Models\ContentArticleSource::factory()
    ->for($article, 'article')
    ->create([
        'publisher' => 'N6 smoke source',
        'title' => 'N6 scheduled publication source',
        'url' => 'https://example.test/n6-scheduled-smoke',
        'is_publicly_cited' => true,
    ]);

$service = app(\App\Support\ContentArticlePublishingService::class);
$reviewed = $service->markReviewed($article);
$scheduled = $service->schedule($reviewed, now()->addSeconds($delay));

file_put_contents(
    base_path('output/newsroom-scheduled-publication-smoke/fixture.json'),
    json_encode([
        'id' => $scheduled->id,
        'slug' => $scheduled->slug,
        'title' => $scheduled->title,
        'scheduled_for' => $scheduled->scheduled_for?->toAtomString(),
        'public_path' => \App\Support\NewsroomRouteContract::canonicalPath(
            $scheduled->type?->value ?? (string) $scheduled->type,
            (string) $scheduled->slug,
        ),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);
}

async function readCoordinatorState() {
    const php = String.raw`
$coordinator = app(\App\Support\NewsroomSeoArtifactRefreshCoordinator::class);
file_put_contents(
    base_path('output/newsroom-scheduled-publication-smoke/coordinator.json'),
    json_encode([
        'current_version' => $coordinator->currentVersion(),
        'clean_version' => $coordinator->cleanVersion(),
        'dirty' => $coordinator->isDirty(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);

    return JSON.parse(await fs.readFile(coordinatorPath, 'utf8'));
}

async function readArticleState() {
    const php = String.raw`
$article = \App\Models\ContentArticle::query()
    ->where('slug', (string) env('N6_SCHEDULED_SMOKE_SLUG'))
    ->firstOrFail();

$audit = \App\Models\AuditLog::query()
    ->where('action', 'content_article.published')
    ->where('entity_type', \App\Models\ContentArticle::class)
    ->where('entity_id', (string) $article->id)
    ->latest('id')
    ->first();

file_put_contents(
    base_path('output/newsroom-scheduled-publication-smoke/article-state.json'),
    json_encode([
        'workflow_status' => $article->workflow_status?->value ?? (string) $article->workflow_status,
        'scheduled_for' => $article->scheduled_for?->toAtomString(),
        'first_published_at' => $article->first_published_at?->toAtomString(),
        'published_at' => $article->published_at?->toAtomString(),
        'publish_trigger' => $audit?->metadata['trigger'] ?? null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);
`;

    await runCommand('php', ['artisan', 'tinker', '--execute', php]);

    return JSON.parse(await fs.readFile(articleStatePath, 'utf8'));
}

function startLaravelServer() {
    const publicDir = path.join(cwd, 'public');
    const router = path.join(
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

    const child = spawn('php', ['-S', `127.0.0.1:${port}`, router], {
        cwd: publicDir,
        env: {
            ...process.env,
            NEWSROOM_PUBLIC_ENABLED: 'true',
            PHP_CLI_SERVER_WORKERS: '4',
        },
        stdio: ['ignore', 'pipe', 'pipe'],
    });

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

        await sleep(250);
    }

    throw new Error(`Laravel server did not become ready at ${url}.`);
}

async function fetchText(url) {
    const response = await fetch(url, {
        redirect: 'manual',
        headers: {
            Accept: 'text/html,application/atom+xml,application/xml;q=0.9,*/*;q=0.8',
        },
    });

    return {
        status: response.status,
        body: await response.text(),
    };
}

async function readRequiredFile(file) {
    try {
        return await fs.readFile(file, 'utf8');
    } catch (error) {
        throw new Error(`Required smoke artifact is missing: ${file}. ${error instanceof Error ? error.message : String(error)}`);
    }
}

async function runCommand(command, args) {
    const result = await runCommandCapture(command, args);

    if (result.code !== 0) {
        throw new Error(`${command} ${args.join(' ')} exited with code ${result.code}.\n${result.output}`);
    }

    return result.output;
}

async function runCommandCapture(command, args) {
    return await new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            cwd,
            env: process.env,
            stdio: ['ignore', 'pipe', 'pipe'],
        });

        let output = '';

        child.stdout.on('data', (chunk) => {
            const text = chunk.toString();
            output += text;
            process.stdout.write(text);
        });

        child.stderr.on('data', (chunk) => {
            const text = chunk.toString();
            output += text;
            process.stderr.write(text);
        });

        child.once('error', reject);
        child.once('exit', (code) => resolve({ code: code ?? 1, output }));
    });
}

async function stopProcess(child) {
    if (!child || child.exitCode !== null) {
        return;
    }

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

function sha256(value) {
    return createHash('sha256').update(value).digest('hex');
}

function assert(condition, message) {
    if (!condition) {
        throw new Error(message);
    }
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}
