<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    cleanupIndexNowSubmitReports();

    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('indexnow.enabled', true);
    config()->set('indexnow.endpoint', 'https://api.indexnow.test/indexnow');
    config()->set('indexnow.host', 'prawkonaraz.pl');
    config()->set('indexnow.key', 'indexnow-test-submit');
    config()->set('indexnow.key_location', null);
    config()->set('indexnow.timeout', 3);
    config()->set('indexnow.max_urls_per_request', 10000);

    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');
});

afterEach(function (): void {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
    cleanupIndexNowSubmitReports();
});

function cleanupIndexNowSubmitReports(): void
{
    File::deleteDirectory(storage_path('app/testing-indexnow-submit'));
}

test('indexnow submit dry-run validates urls without sending http requests', function () {
    config()->set('indexnow.enabled', false);

    Http::fake();

    $this->artisan('seo:indexnow-submit', [
        'url' => [
            '/pytanie/99/czy-mozesz-jechac',
            'https://evil.test/pytanie/99/czy-mozesz-jechac',
            'http://prawkonaraz.pl/pytanie/99/czy-mozesz-jechac',
            '/admin',
        ],
        '--dry-run' => true,
    ])
        ->expectsOutput('IndexNow URLs: input=4 accepted=1 rejected=3')
        ->expectsOutput('IndexNow requests: planned=1 sent=0 accepted=0 failed=0')
        ->expectsOutput('IndexNow submit dry-run finished. No HTTP requests were sent.')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('indexnow submit sends a valid payload for accepted urls', function () {
    Http::fake([
        'https://api.indexnow.test/indexnow' => Http::response('', 200),
    ]);

    $this->artisan('seo:indexnow-submit', [
        'url' => [
            '/pytanie/99/czy-mozesz-jechac',
            'https://prawkonaraz.pl/znaki-drogowe',
        ],
    ])
        ->expectsOutput('IndexNow URLs: input=2 accepted=2 rejected=0')
        ->expectsOutput('IndexNow requests: planned=1 sent=1 accepted=1 failed=0')
        ->expectsOutput('Request #1 urls=2 status=200 accepted=yes reason=ok')
        ->expectsOutput('IndexNow submit finished.')
        ->assertSuccessful();

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.indexnow.test/indexnow'
            && $request['host'] === 'prawkonaraz.pl'
            && $request['key'] === 'indexnow-test-submit'
            && $request['keyLocation'] === 'https://prawkonaraz.pl/indexnow-test-submit.txt'
            && $request['urlList'] === [
                'https://prawkonaraz.pl/pytanie/99/czy-mozesz-jechac',
                'https://prawkonaraz.pl/znaki-drogowe',
            ];
    });
});

test('indexnow submit can read the key from a local source file', function () {
    config()->set('indexnow.key', null);

    $sourcePath = storage_path('app/testing-indexnow-submit/key.txt');
    File::ensureDirectoryExists(dirname($sourcePath));
    File::put($sourcePath, 'd0eff-test-submit-source'.PHP_EOL);

    Http::fake([
        'https://api.indexnow.test/indexnow' => Http::response('', 200),
    ]);

    $this->artisan('seo:indexnow-submit', [
        'url' => ['/znaki-drogowe'],
        '--key-source' => $sourcePath,
    ])
        ->expectsOutput('IndexNow URLs: input=1 accepted=1 rejected=0')
        ->expectsOutput('IndexNow submit finished.')
        ->assertSuccessful();

    Http::assertSent(function (Request $request): bool {
        return $request['key'] === 'd0eff-test-submit-source'
            && $request['keyLocation'] === 'https://prawkonaraz.pl/d0eff-test-submit-source.txt';
    });
});

test('indexnow submit fails when the key source file is missing', function () {
    $sourcePath = storage_path('app/testing-indexnow-submit/missing.txt');

    $this->artisan('seo:indexnow-submit', [
        'url' => ['/znaki-drogowe'],
        '--key-source' => $sourcePath,
    ])
        ->expectsOutput('IndexNow key source file does not exist: '.$sourcePath)
        ->assertFailed();
});

test('indexnow submit chunks urls and accepts 200 and 202 statuses', function () {
    config()->set('indexnow.max_urls_per_request', 2);

    Http::fake([
        'https://api.indexnow.test/indexnow' => Http::sequence()
            ->push('', 200)
            ->push('', 202),
    ]);

    $this->artisan('seo:indexnow-submit', [
        'url' => [
            '/pytanie/1/a',
            '/pytanie/2/b',
            '/pytanie/3/c',
        ],
    ])
        ->expectsOutput('IndexNow requests: planned=2 sent=2 accepted=2 failed=0')
        ->expectsOutput('Request #1 urls=2 status=200 accepted=yes reason=ok')
        ->expectsOutput('Request #2 urls=1 status=202 accepted=yes reason=accepted_pending_validation')
        ->assertSuccessful();

    Http::assertSentCount(2);
});

test('indexnow submit reports rate limits without crashing', function () {
    Http::fake([
        'https://api.indexnow.test/indexnow' => Http::response('', 429),
    ]);

    $this->artisan('seo:indexnow-submit', [
        'url' => ['/pytanie/99/czy-mozesz-jechac'],
    ])
        ->expectsOutput('IndexNow requests: planned=1 sent=1 accepted=0 failed=1')
        ->expectsOutput('Request #1 urls=1 status=429 accepted=no reason=rate_limited')
        ->assertFailed();
});

test('indexnow submit can collect urls from sitemap sources and write a report', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);
    Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '101',
        'prompt' => 'Czy możesz jechać dalej?',
    ]);

    Http::fake();

    $reportPath = storage_path('app/testing-indexnow-submit/report.json');

    $this->artisan('seo:indexnow-submit', [
        '--from-sitemap' => true,
        '--dry-run' => true,
        '--limit' => 5000,
        '--report' => $reportPath,
    ])
        ->expectsOutputToContain('Collected ')
        ->expectsOutputToContain('IndexNow URLs: input=')
        ->expectsOutput('IndexNow submit dry-run finished. No HTTP requests were sent.')
        ->expectsOutput('IndexNow report written: '.$reportPath)
        ->assertSuccessful();

    Http::assertNothingSent();

    $report = json_decode(File::get($reportPath), true);

    expect($report['dry_run'])->toBeTrue();
    expect($report['summary']['accepted_urls'])->toBeGreaterThan(0);
    expect($report['accepted_urls'])
        ->toContain('https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy')
        ->toContain('https://prawkonaraz.pl/pytanie/101/czy-mozesz-jechac-dalej');
});

test('indexnow submit requires urls or sitemap source', function () {
    $this->artisan('seo:indexnow-submit')
        ->expectsOutput('Provide at least one URL/path or use --from-sitemap.')
        ->assertFailed();
});
