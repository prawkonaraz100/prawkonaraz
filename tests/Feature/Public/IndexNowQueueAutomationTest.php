<?php

use App\Models\IndexNowUrlSubmission;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use App\Support\PublicQuestionCatalogService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('indexnow.enabled', true);
    config()->set('indexnow.automation_enabled', true);
    config()->set('indexnow.endpoint', 'https://api.indexnow.test/indexnow');
    config()->set('indexnow.host', 'prawkonaraz.pl');
    config()->set('indexnow.key', 'indexnow-test-queue');
    config()->set('indexnow.key_source', null);
    config()->set('indexnow.key_location', null);
    config()->set('indexnow.timeout', 3);
    config()->set('indexnow.max_urls_per_request', 10000);
    config()->set('indexnow.queue_batch_size', 50);
    config()->set('indexnow.queue_debounce_minutes', 0);
    config()->set('indexnow.queue_retry_minutes', 60);

    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');
});

afterEach(function (): void {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

test('question public explanation changes queue the canonical question url', function () {
    $category = LicenseCategory::factory()->categoryB()->create(['sort_order' => 1]);
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '8216',
        'prompt' => 'Czy w tej sytuacji masz prawo wykonać manewr cofania?',
    ]);

    $expectedUrl = app(PublicQuestionCatalogService::class)
        ->findCanonicalUrlByExternalId('8216');

    $explanation = QuestionPublicExplanation::factory()
        ->forQuestionExternalId($question)
        ->published()
        ->create([
            'body' => 'Pierwsza wersja publicznego wyjaśnienia.',
        ]);

    $submission = IndexNowUrlSubmission::query()->firstOrFail();

    expect($submission->url)->toBe($expectedUrl)
        ->and($submission->status)->toBe(IndexNowUrlSubmission::STATUS_PENDING)
        ->and($submission->source)->toBe('question_public_explanation')
        ->and($submission->event_type)->toBe(IndexNowUrlSubmission::EVENT_CREATED)
        ->and($submission->enqueued_count)->toBe(1);

    $explanation->update([
        'body' => 'Zaktualizowana wersja publicznego wyjaśnienia.',
    ]);

    $submission->refresh();

    expect(IndexNowUrlSubmission::query()->count())->toBe(1)
        ->and($submission->url)->toBe($expectedUrl)
        ->and($submission->event_type)->toBe(IndexNowUrlSubmission::EVENT_UPDATED)
        ->and($submission->enqueued_count)->toBe(2);
});

test('public explanation backfill dry-run resolves urls without writing queue rows', function () {
    config()->set('indexnow.automation_enabled', false);

    $category = LicenseCategory::factory()->categoryB()->create(['sort_order' => 1]);
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '9001',
        'prompt' => 'Czy możesz kontynuować jazdę?',
    ]);

    QuestionPublicExplanation::factory()
        ->forQuestionExternalId($question)
        ->published()
        ->create(['body' => 'Wyjaśnienie do sprawdzenia w dry-runie.']);

    $this->artisan('seo:indexnow-enqueue-public-explanations', [
        '--updated-since' => now()->subDay()->toDateString(),
        '--dry-run' => true,
    ])
        ->expectsOutput('IndexNow public explanations: matched=1 resolved=1 skipped=0')
        ->expectsOutput('IndexNow public explanations dry-run finished. No queue rows were written.')
        ->assertSuccessful();

    expect(IndexNowUrlSubmission::query()->count())->toBe(0);
});

test('public explanation backfill can queue urls even before scheduler automation is enabled', function () {
    config()->set('indexnow.automation_enabled', false);

    $category = LicenseCategory::factory()->categoryB()->create(['sort_order' => 1]);
    $question = Question::factory()->create([
        'license_category_id' => $category->getKey(),
        'external_id' => '9002',
        'prompt' => 'Czy możesz zatrzymać pojazd?',
    ]);

    QuestionPublicExplanation::factory()
        ->forQuestionExternalId($question)
        ->published()
        ->create(['body' => 'Wyjaśnienie do kolejki.']);

    $this->artisan('seo:indexnow-enqueue-public-explanations', [
        '--updated-since' => now()->subDay()->toDateString(),
    ])
        ->expectsOutput('IndexNow public explanations: matched=1 resolved=1 skipped=0')
        ->expectsOutput('IndexNow public explanation URLs queued.')
        ->assertSuccessful();

    expect(IndexNowUrlSubmission::query()->count())->toBe(1)
        ->and(IndexNowUrlSubmission::query()->first()?->source)->toBe('question_public_explanation_backfill');
});

test('indexnow queue drain submits a small due batch and marks rows as sent', function () {
    $submission = IndexNowUrlSubmission::query()->create([
        'url' => 'https://prawkonaraz.pl/pytanie/8216/czy-mozesz-cofac',
        'url_hash' => IndexNowUrlSubmission::hashUrl('https://prawkonaraz.pl/pytanie/8216/czy-mozesz-cofac'),
        'status' => IndexNowUrlSubmission::STATUS_PENDING,
        'source' => 'test',
        'event_type' => IndexNowUrlSubmission::EVENT_UPDATED,
        'available_at' => now()->subMinute(),
        'last_enqueued_at' => now()->subMinute(),
    ]);

    Http::fake([
        'https://api.indexnow.test/indexnow' => Http::response('', 200),
    ]);

    $this->artisan('seo:indexnow-drain-queue', [
        '--limit' => 10,
    ])
        ->expectsOutput('IndexNow queue: selected=1 limit=10')
        ->expectsOutput('IndexNow requests: planned=1 sent=1 accepted=1 failed=0')
        ->expectsOutput('Request #1 urls=1 status=200 accepted=yes reason=ok')
        ->expectsOutput('IndexNow queue batch marked as sent.')
        ->assertSuccessful();

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.indexnow.test/indexnow'
            && $request['urlList'] === ['https://prawkonaraz.pl/pytanie/8216/czy-mozesz-cofac'];
    });

    $submission->refresh();

    expect($submission->status)->toBe(IndexNowUrlSubmission::STATUS_SENT)
        ->and($submission->attempts)->toBe(0)
        ->and($submission->last_http_status)->toBe(200)
        ->and($submission->last_submitted_at)->not->toBeNull();
});

test('indexnow queue drain is a no-op when automation is disabled', function () {
    config()->set('indexnow.automation_enabled', false);

    IndexNowUrlSubmission::query()->create([
        'url' => 'https://prawkonaraz.pl/pytanie/1/test',
        'url_hash' => IndexNowUrlSubmission::hashUrl('https://prawkonaraz.pl/pytanie/1/test'),
        'status' => IndexNowUrlSubmission::STATUS_PENDING,
        'source' => 'test',
        'event_type' => IndexNowUrlSubmission::EVENT_UPDATED,
        'available_at' => now()->subMinute(),
        'last_enqueued_at' => now()->subMinute(),
    ]);

    Http::fake();

    $this->artisan('seo:indexnow-drain-queue')
        ->expectsOutput('IndexNow automation is disabled. No queued URLs were submitted.')
        ->assertSuccessful();

    Http::assertNothingSent();

    expect(IndexNowUrlSubmission::query()->first()?->status)->toBe(IndexNowUrlSubmission::STATUS_PENDING);
});
