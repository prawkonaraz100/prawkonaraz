<?php

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use App\Models\ContentTag;
use App\Models\ContentTopic;
use App\Models\LegalAct;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Models\User;
use App\Support\NewsroomBodyContract;

test('content article casts canonical newsroom fields and uses slug route key', function () {
    $article = ContentArticle::factory()->create([
        'type' => ContentArticleType::Analysis->value,
        'origin_type' => ContentArticleOriginType::DataAnalysis->value,
        'regulatory_status' => ContentArticleRegulatoryStatus::Consultation->value,
        'workflow_status' => ContentArticleWorkflowStatus::Draft->value,
        'effective_from' => now()->addDay()->toDateString(),
        'key_points' => ['Pierwszy punkt'],
        'is_featured' => true,
        'editorial_priority' => 7,
    ]);

    expect($article->type)->toBe(ContentArticleType::Analysis)
        ->and($article->origin_type)->toBe(ContentArticleOriginType::DataAnalysis)
        ->and($article->regulatory_status)->toBe(ContentArticleRegulatoryStatus::Consultation)
        ->and($article->workflow_status)->toBe(ContentArticleWorkflowStatus::Draft)
        ->and($article->body_blocks)->toBeArray()
        ->and($article->body_schema_version)->toBe(NewsroomBodyContract::CURRENT_SCHEMA_VERSION)
        ->and($article->key_points)->toBe(['Pierwszy punkt'])
        ->and($article->is_featured)->toBeTrue()
        ->and($article->editorial_priority)->toBe(7)
        ->and($article->getRouteKeyName())->toBe('slug');
});

test('public visibility active distribution and indexability remain distinct', function () {
    $published = ContentArticle::factory()->published()->create();
    $noindex = ContentArticle::factory()->published()->noindex()->create();
    $needsReview = ContentArticle::factory()->needsReview()->create();
    $archived = ContentArticle::factory()->archived()->create();
    $withdrawn = ContentArticle::factory()->withdrawn()->create();
    $draft = ContentArticle::factory()->create();
    $neverPublishedArchive = ContentArticle::factory()->create([
        'workflow_status' => ContentArticleWorkflowStatus::Archived->value,
        'archived_at' => now()->subMinute(),
        'first_published_at' => null,
    ]);

    $publicIds = ContentArticle::query()->publiclyVisible()->pluck('id')->all();
    $activeIds = ContentArticle::query()->activelyDistributed()->pluck('id')->all();
    $indexableIds = ContentArticle::query()->indexable()->pluck('id')->all();

    expect($publicIds)
        ->toContain($published->id, $noindex->id, $needsReview->id, $archived->id)
        ->not->toContain($withdrawn->id, $draft->id, $neverPublishedArchive->id);

    expect($activeIds)
        ->toContain($published->id, $noindex->id)
        ->not->toContain($needsReview->id, $archived->id, $withdrawn->id);

    expect($indexableIds)
        ->toContain($published->id, $needsReview->id, $archived->id)
        ->not->toContain($noindex->id, $withdrawn->id, $neverPublishedArchive->id);

    expect($archived->fresh()->isPubliclyVisible())->toBeTrue()
        ->and($archived->fresh()->isActivelyDistributed())->toBeFalse()
        ->and($noindex->fresh()->isIndexable())->toBeFalse();
});

test('category and article category scopes preserve active publication invariant', function () {
    $activeCategory = ContentCategory::factory()->create(['position' => 10]);
    $inactiveCategory = ContentCategory::factory()->inactive()->create(['position' => 20]);
    $emptyCategory = ContentCategory::factory()->create(['position' => 30]);

    $activeArticle = ContentArticle::factory()->published()->for($activeCategory, 'category')->create();
    $inactiveArticle = ContentArticle::factory()->published()->for($inactiveCategory, 'category')->create();

    expect(ContentCategory::query()->active()->pluck('id')->all())
        ->toContain($activeCategory->id)
        ->not->toContain($inactiveCategory->id);

    expect(ContentArticle::query()->forCategory($activeCategory)->pluck('id')->all())
        ->toBe([$activeArticle->id]);

    expect($activeArticle->hasActiveCategory())->toBeTrue()
        ->and($inactiveArticle->hasActiveCategory())->toBeFalse()
        ->and($activeCategory->isPublicationEligible())->toBeTrue()
        ->and($inactiveCategory->isPublicationEligible())->toBeFalse()
        ->and($activeCategory->hasPubliclyVisibleArticles())->toBeTrue()
        ->and($activeCategory->hasActivelyDistributedArticles())->toBeTrue()
        ->and($activeCategory->canBeDeactivated())->toBeFalse()
        ->and($emptyCategory->canBeDeactivated())->toBeTrue();
});

test('topic publication corpus baseline does not redefine published url visibility', function () {
    $topic = ContentTopic::factory()->published()->create();
    $articles = ContentArticle::factory()->published()->count(3)->create();

    $topic->articles()->attach($articles->pluck('id')->all());

    expect($topic->meetsPublicationCorpusBaseline())->toBeTrue()
        ->and($topic->hasEligibleFeaturedArticle())->toBeTrue()
        ->and($topic->meetsPublicationRequirements())->toBeTrue();

    $topic->update(['featured_article_id' => $articles[0]->id]);

    expect($topic->fresh()->hasEligibleFeaturedArticle())->toBeTrue();

    $draft = ContentArticle::factory()->create();
    $topic->articles()->attach($draft->id);
    $topic->update(['featured_article_id' => $draft->id]);

    expect($topic->fresh()->hasEligibleFeaturedArticle())->toBeFalse()
        ->and($topic->fresh()->meetsPublicationRequirements())->toBeFalse();

    $topic->update(['featured_article_id' => null]);
    $topic->articles()->detach([$draft->id, $articles[2]->id]);

    expect($topic->fresh()->meetsPublicationCorpusBaseline())->toBeFalse()
        ->and(ContentTopic::query()->published()->whereKey($topic->id)->exists())->toBeTrue()
        ->and($topic->fresh()->isPubliclyVisible())->toBeTrue();
});

test('active breaking scope requires live published news and future expiry', function () {
    $activeBreaking = ContentArticle::factory()->breaking()->create();

    $expiredBreaking = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'is_breaking' => true,
        'breaking_expires_at' => now()->subMinute(),
    ]);

    $wrongTypeBreaking = ContentArticle::factory()->published()->guide()->create([
        'is_breaking' => true,
        'breaking_expires_at' => now()->addHour(),
    ]);

    $ids = ContentArticle::query()->activeBreaking()->pluck('id')->all();

    expect($ids)
        ->toContain($activeBreaking->id)
        ->not->toContain($expiredBreaking->id, $wrongTypeBreaking->id);
});

test('article sources tags topics authors and placements expose documented relations', function () {
    $author = ContentAuthor::factory()->published()->create();
    $reviewer = ContentAuthor::factory()->published()->create();
    $article = ContentArticle::factory()->published()->create([
        'author_id' => $author->id,
        'reviewer_id' => $reviewer->id,
    ]);

    $publicSource = ContentArticleSource::factory()->for($article, 'article')->create([
        'sort_order' => 10,
    ]);
    $privateSource = ContentArticleSource::factory()->for($article, 'article')->privateEvidence()->create([
        'sort_order' => 20,
    ]);

    $tag = ContentTag::factory()->create();
    $topic = ContentTopic::factory()->create();
    $article->tags()->attach($tag->id);
    $article->topics()->attach($topic->id);

    $creator = User::factory()->admin()->create();
    $placement = ContentHomePlacement::factory()->for($article, 'article')->create([
        'created_by_user_id' => $creator->id,
        'updated_by_user_id' => $creator->id,
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addHour(),
    ]);

    expect($article->fresh()->sources->pluck('id')->all())
        ->toBe([$publicSource->id, $privateSource->id])
        ->and($publicSource->fresh()->source_type)->toBe(ContentArticleSourceType::Official)
        ->and(ContentArticleSource::query()->publiclyCited()->pluck('id')->all())
        ->toContain($publicSource->id)
        ->not->toContain($privateSource->id)
        ->and($article->fresh()->tags->pluck('id')->all())->toContain($tag->id)
        ->and($tag->fresh()->articles->pluck('id')->all())->toContain($article->id)
        ->and($article->fresh()->topics->pluck('id')->all())->toContain($topic->id)
        ->and($topic->fresh()->articles->pluck('id')->all())->toContain($article->id)
        ->and($author->fresh()->authoredContentArticles->pluck('id')->all())->toContain($article->id)
        ->and($reviewer->fresh()->reviewedContentArticles->pluck('id')->all())->toContain($article->id)
        ->and(ContentHomePlacement::query()->activeAt()->pluck('id')->all())->toContain($placement->id)
        ->and($placement->fresh()->createdBy->is($creator))->toBeTrue()
        ->and($placement->fresh()->updatedBy->is($creator))->toBeTrue();
});

test('question legal unit and traffic sign pivots are bidirectional with metadata', function () {
    $article = ContentArticle::factory()->create();
    $question = Question::factory()->create();
    $trafficSign = TrafficSign::factory()->create();

    $legalAct = LegalAct::query()->create([
        'slug' => 'test-act-'.strtolower(str()->random(6)),
        'title' => 'Testowy akt prawny',
        'source_url' => 'https://example.test/act',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);

    $legalUnit = LegalUnit::query()->create([
        'legal_act_id' => $legalAct->id,
        'type' => 'article',
        'label' => 'Art. 1',
        'slug' => 'art-1',
        'title' => 'Testowa jednostka prawna',
        'source_url' => 'https://example.test/act#art-1',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);

    $article->questions()->attach($question->id, [
        'relation_type' => 'practice',
        'sort_order' => 20,
        'note' => 'Ćwiczenie do materiału',
    ]);

    $article->legalUnits()->attach($legalUnit->id, [
        'relation_type' => 'direct_basis',
        'sort_order' => 10,
        'note' => 'Podstawa prawna',
    ]);

    $article->trafficSigns()->attach($trafficSign->id, [
        'relation_type' => 'example',
        'sort_order' => 30,
    ]);

    $questionFromArticle = $article->fresh()->questions->first();
    $legalFromArticle = $article->fresh()->legalUnits->first();
    $signFromArticle = $article->fresh()->trafficSigns->first();

    expect($questionFromArticle?->id)->toBe($question->id)
        ->and($questionFromArticle?->pivot?->relation_type)->toBe('practice')
        ->and($questionFromArticle?->pivot?->sort_order)->toBe(20)
        ->and($legalFromArticle?->id)->toBe($legalUnit->id)
        ->and($legalFromArticle?->pivot?->relation_type)->toBe('direct_basis')
        ->and($legalFromArticle?->pivot?->note)->toBe('Podstawa prawna')
        ->and($signFromArticle?->id)->toBe($trafficSign->id)
        ->and($signFromArticle?->pivot?->relation_type)->toBe('example')
        ->and($question->fresh()->contentArticles->pluck('id')->all())->toContain($article->id)
        ->and($legalUnit->fresh()->contentArticles->pluck('id')->all())->toContain($article->id)
        ->and($trafficSign->fresh()->contentArticles->pluck('id')->all())->toContain($article->id);
});

test('scheduled featured and freshness scopes remain independent', function () {
    $scheduled = ContentArticle::factory()->scheduled()->create();
    $featured = ContentArticle::factory()->featured()->create();
    $due = ContentArticle::factory()->create([
        'freshness_review_due_at' => now()->subMinute(),
    ]);
    $futureReview = ContentArticle::factory()->create([
        'freshness_review_due_at' => now()->addDay(),
    ]);

    expect(ContentArticle::query()->scheduled()->pluck('id')->all())->toContain($scheduled->id)
        ->and(ContentArticle::query()->featured()->pluck('id')->all())->toContain($featured->id)
        ->and(ContentArticle::query()->needsFreshnessReview()->pluck('id')->all())
        ->toContain($due->id)
        ->not->toContain($futureReview->id);
});
