<?php

use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentTopic;
use App\Models\LegalAct;
use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Support\NewsroomSemanticLinkService;
use App\Support\PublicQuestionCatalogService;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('semantic resolver is deterministic bounded and excludes ineligible targets', function () {
    Carbon::setTestNow('2026-09-18 10:00:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy-semantic',
    ]);
    $otherCategory = ContentCategory::factory()->create([
        'name' => 'Przepisy',
        'slug' => 'przepisy-semantic',
    ]);
    $topic = ContentTopic::factory()->published()->create([
        'title' => 'Egzamin praktyczny',
        'slug' => 'egzamin-praktyczny-semantic',
    ]);

    $source = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Materiał źródłowy',
        'slug' => 'material-zrodlowy-semantic',
    ]);
    $sharedTopic = ContentArticle::factory()->published()->create([
        'category_id' => $otherCategory->id,
        'title' => 'Wspólny temat',
        'slug' => 'wspolny-temat-semantic',
        'editorial_priority' => 0,
        'first_published_at' => now()->subHours(4),
        'published_at' => now()->subHours(4),
    ]);
    $sameCategory = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Ta sama kategoria',
        'slug' => 'ta-sama-kategoria-semantic',
        'editorial_priority' => 100,
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ]);
    $noindex = ContentArticle::factory()->published()->noindex()->create([
        'category_id' => $category->id,
        'title' => 'Noindex ukryty',
        'slug' => 'noindex-ukryty-semantic',
        'editorial_priority' => 999,
    ]);
    $draft = ContentArticle::factory()->draft()->create([
        'category_id' => $category->id,
        'title' => 'Draft ukryty',
        'slug' => 'draft-ukryty-semantic',
    ]);

    $topic->articles()->attach([$source->id, $sharedTopic->id]);
    $service = app(NewsroomSemanticLinkService::class);
    $related = $service->relatedArticles($source);

    expect(array_column($related, 'id'))->toBe([$sharedTopic->id, $sameCategory->id])
        ->and(collect($related)->pluck('id'))->not->toContain($noindex->id)
        ->and(collect($related)->pluck('id'))->not->toContain($draft->id)
        ->and($service->topics($source))->toHaveCount(1)
        ->and($service->topics($source)[0]['url'])->toBe(
            route('public.news.topics.show', ['topicSlug' => $topic->slug], false),
        );

    $question = Question::factory()->create();
    foreach (range(1, 5) as $index) {
        $article = ContentArticle::factory()->published()->create([
            'category_id' => $category->id,
            'title' => "Reverse article {$index}",
            'slug' => "reverse-article-{$index}",
            'editorial_priority' => 100 - $index,
        ]);
        $article->questions()->attach($question->id, [
            'relation_type' => 'direct',
            'sort_order' => $index,
            'note' => null,
        ]);
    }

    $hiddenReverse = ContentArticle::factory()->published()->noindex()->create([
        'category_id' => $category->id,
        'title' => 'Reverse noindex hidden',
        'slug' => 'reverse-noindex-hidden',
        'editorial_priority' => 1000,
    ]);
    $hiddenReverse->questions()->attach($question->id, [
        'relation_type' => 'direct',
        'sort_order' => 0,
        'note' => null,
    ]);

    $reverse = $service->forQuestions([$question->id]);

    expect($reverse)->toHaveCount(NewsroomSemanticLinkService::REVERSE_LIMIT)
        ->and(array_column($reverse, 'title'))->toBe([
            'Reverse article 1',
            'Reverse article 2',
            'Reverse article 3',
        ])
        ->and(collect($reverse)->pluck('id'))->not->toContain($hiddenReverse->id);

    $audit = $service->audit($source->fresh(['category', 'topics']));

    expect($audit['has_crawlable_inbound'])->toBeTrue()
        ->and($audit['hub']['url'])->toBe(route('public.news', absolute: false))
        ->and($audit['category']['url'])->toBe(
            route('public.news.categories.show', ['categorySlug' => $category->slug], false),
        )
        ->and($audit['topics'])->toHaveCount(1)
        ->and(collect($audit['inbound_sources'])->pluck('kind'))->toContain('hub', 'category', 'topic', 'author')
        ->and($audit['estimated_hub_depth'])->toBe(2);

    $guide = ContentArticle::factory()->published()->guide()->create([
        'category_id' => $category->id,
        'title' => 'Poradnik z bezpośrednim inboundem',
        'slug' => 'poradnik-z-bezposrednim-inboundem',
    ]);
    $guideAudit = $service->audit($guide->fresh(['category', 'author']));

    expect($guideAudit['has_crawlable_inbound'])->toBeTrue()
        ->and($guideAudit['hub']['url'])->toBe(route('public.guides', absolute: false))
        ->and($guideAudit['estimated_hub_depth'])->toBe(1);

    $needsReview = ContentArticle::factory()->needsReview()->create([
        'category_id' => $category->id,
        'title' => 'Materiał w ponownej weryfikacji',
        'slug' => 'material-w-ponownej-weryfikacji-audit',
    ]);
    $needsReviewAudit = $service->audit($needsReview->fresh(['category', 'author']));

    expect($needsReviewAudit['has_crawlable_inbound'])->toBeTrue()
        ->and($needsReviewAudit['hub'])->toBeNull()
        ->and(collect($needsReviewAudit['inbound_sources'])->pluck('kind')->all())->toBe(['author'])
        ->and($needsReviewAudit['estimated_hub_depth'])->toBe(3);

    $noindexAudit = $service->audit($noindex->fresh(['category', 'author']));

    expect($noindexAudit['has_crawlable_inbound'])->toBeFalse()
        ->and($noindexAudit['inbound_sources'])->toBe([])
        ->and($noindexAudit['estimated_hub_depth'])->toBeNull();
});

test('article page renders crawlable category topic and deterministic related links', function () {
    Carbon::setTestNow('2026-09-18 10:30:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Praktyka',
        'slug' => 'praktyka-semantic',
    ]);
    $topic = ContentTopic::factory()->published()->create([
        'title' => 'Manewry egzaminacyjne',
        'slug' => 'manewry-egzaminacyjne-semantic',
    ]);

    $guide = ContentArticle::factory()->published()->guide()->create([
        'category_id' => $category->id,
        'title' => 'Jak wykonać manewr',
        'slug' => 'jak-wykonac-manewr-semantic',
    ]);
    $related = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Najczęstsze błędy na placu',
        'slug' => 'najczestsze-bledy-na-placu-semantic',
        'editorial_priority' => 50,
    ]);
    $topic->articles()->attach([$guide->id, $related->id]);

    $response = $this->get(route('public.guides.show', ['articleSlug' => $guide->slug]));

    $response
        ->assertOk()
        ->assertSee(route('public.news.categories.show', ['categorySlug' => $category->slug], false), false)
        ->assertSee(route('public.news.topics.show', ['topicSlug' => $topic->slug], false), false)
        ->assertSee('Manewry egzaminacyjne')
        ->assertSee('Powiązane materiały')
        ->assertSee('Najczęstsze błędy na placu')
        ->assertSee('/aktualnosci/'.$related->slug, false);
});

test('question page renders bounded reverse newsroom links without mutating other question graphs', function () {
    config(['newsroom.public_enabled' => true]);

    $licenseCategory = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()
        ->for($licenseCategory, 'licenseCategory')
        ->create([
            'external_id' => 'N4008-Q1',
            'prompt' => 'Czy w tej sytuacji należy zachować szczególną ostrożność?',
        ]);

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Jak rozumieć szczególną ostrożność',
        'slug' => 'szczegolna-ostroznosc-reverse',
    ]);
    $article->questions()->attach($question->id, [
        'relation_type' => 'direct',
        'sort_order' => 0,
        'note' => 'Jawna relacja redakcyjna.',
    ]);

    expect($question->contentArticles()->count())->toBe(1)
        ->and($question->legalArticleTopicCandidates()->count())->toBe(0);

    $url = app(PublicQuestionCatalogService::class)->questionUrl($question);

    $this->get($url)
        ->assertOk()
        ->assertSee('Materiały powiązane z tym pytaniem')
        ->assertSee('Jak rozumieć szczególną ostrożność')
        ->assertSee('/aktualnosci/'.$article->slug, false);
});

test('legal content page renders reverse links only from explicit legal-unit pivots', function () {
    config(['newsroom.public_enabled' => true]);

    [$page, $unit] = semanticLegalPageFixture();

    $linked = ContentArticle::factory()->published()->create([
        'title' => 'Co wynika z art. 26',
        'slug' => 'co-wynika-z-art-26-reverse',
    ]);
    $linked->legalUnits()->attach($unit->id, [
        'relation_type' => 'direct_basis',
        'sort_order' => 0,
        'note' => 'Jawna podstawa prawna.',
    ]);

    $unrelated = ContentArticle::factory()->published()->create([
        'title' => 'Niepowiązany newsroom article',
        'slug' => 'niepowiazany-newsroom-article-reverse',
    ]);

    $this->get(route('public.regulations.show', $page))
        ->assertOk()
        ->assertSee('Materiały powiązane z tym przepisem')
        ->assertSee('Co wynika z art. 26')
        ->assertSee('/aktualnosci/'.$linked->slug, false)
        ->assertDontSee('Niepowiązany newsroom article');

    expect($unrelated->legalUnits()->count())->toBe(0);
});

test('traffic sign page renders reverse links only from explicit sign pivots', function () {
    config(['newsroom.public_enabled' => true]);

    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Redakcja znaków N4-008',
        'slug' => 'redakcja-znakow-n4-008',
    ]);
    $signCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki zakazu N4-008',
        'slug' => 'znaki-zakazu-n4-008',
    ]);
    $sign = TrafficSign::factory()->published()->create([
        'content_author_id' => $author->id,
        'traffic_sign_category_id' => $signCategory->id,
        'code' => 'B-99',
        'slug' => 'b-99-test-reverse',
        'name' => 'Test reverse',
    ]);

    $linked = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Jak czytać ten znak',
        'slug' => 'jak-czytac-ten-znak-reverse',
    ]);
    $linked->trafficSigns()->attach($sign->id, [
        'relation_type' => 'direct',
        'sort_order' => 0,
    ]);

    $this->get(route('traffic-signs.show', $sign->slug))
        ->assertOk()
        ->assertSee('Materiały powiązane z tym znakiem')
        ->assertSee('Jak czytać ten znak')
        ->assertSee('/poradniki/'.$linked->slug, false);
});

test('semantic links disappear from non-newsroom surfaces when public gate is disabled', function () {
    config(['newsroom.public_enabled' => false]);

    $licenseCategory = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()
        ->for($licenseCategory, 'licenseCategory')
        ->create([
            'external_id' => 'N4008-Q2',
            'prompt' => 'Czy ten reverse link powinien być ukryty?',
        ]);

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Ukryty reverse article',
        'slug' => 'ukryty-reverse-article',
    ]);
    $article->questions()->attach($question->id, [
        'relation_type' => 'direct',
        'sort_order' => 0,
        'note' => null,
    ]);

    $url = app(PublicQuestionCatalogService::class)->questionUrl($question);

    $this->get($url)
        ->assertOk()
        ->assertDontSee('Ukryty reverse article')
        ->assertDontSee('Materiały powiązane z tym pytaniem');
});

/**
 * @return array{0:LegalContentPage,1:LegalUnit}
 */
function semanticLegalPageFixture(): array
{
    $act = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym-semantic',
        'title' => 'Prawo o ruchu drogowym',
        'short_title' => 'PoRD',
        'source_url' => 'https://example.test/pord',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $unit = LegalUnit::query()->create([
        'legal_act_id' => $act->id,
        'type' => 'article',
        'label' => 'art. 26',
        'slug' => 'art-26-semantic',
        'title' => 'Obowiązki kierującego wobec pieszego',
        'source_url' => 'https://example.test/pord/art-26',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);
    $topic = LegalTopic::query()->create([
        'slug' => 'piesi-semantic',
        'title' => 'Piesi',
        'status' => LegalTopic::STATUS_PUBLISHED,
        'published_at' => now()->subDay(),
    ]);
    $page = LegalContentPage::query()->create([
        'legal_topic_id' => $topic->id,
        'slug' => 'piesi-semantic',
        'title' => 'Piesi i przejścia',
        'published_at' => now()->subDay(),
        'status' => LegalContentPage::STATUS_PUBLISHED,
    ]);
    $page->legalUnits()->attach($unit->id, [
        'relation_type' => 'direct_basis',
        'sort_order' => 0,
    ]);

    return [$page->fresh('legalUnits.legalAct'), $unit];
}
