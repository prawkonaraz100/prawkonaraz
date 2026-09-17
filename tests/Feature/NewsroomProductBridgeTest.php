<?php

namespace Tests\Feature;

use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\LegalAct;
use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Support\NewsroomBodyContract;
use App\Support\PublicQuestionCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsroomProductBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_article_renders_only_explicit_public_product_bridge_targets(): void
    {
        $licenseCategory = LicenseCategory::factory()->categoryB()->create([
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $linkedQuestion = Question::factory()->for($licenseCategory)->create([
            'external_id' => 'BRIDGE-001',
            'prompt' => 'Czy kierowca powinien ustąpić pierwszeństwa?',
        ]);
        $linkedInactiveQuestion = Question::factory()->for($licenseCategory)->create([
            'external_id' => 'BRIDGE-002',
            'prompt' => 'Nieaktywne pytanie nie może być publiczne?',
            'is_active' => false,
        ]);
        $unlinkedQuestion = Question::factory()->for($licenseCategory)->create([
            'external_id' => 'BRIDGE-003',
            'prompt' => 'Niepowiązane pytanie nie może być publiczne?',
        ]);

        $legalAct = LegalAct::query()->create([
            'slug' => 'prawo-o-ruchu-drogowym-test',
            'title' => 'Prawo o ruchu drogowym — test',
            'short_title' => 'PoRD test',
            'source_url' => 'https://example.com/legal-act',
            'status' => LegalAct::STATUS_VERIFIED,
        ]);
        $legalTopic = LegalTopic::query()->create([
            'slug' => 'pierwszenstwo-test',
            'title' => 'Pierwszeństwo — test',
            'status' => LegalTopic::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $linkedLegalUnit = $this->createLegalUnit($legalAct->getKey(), 'art-1', 'Art. 1', 'Zasada pierwszeństwa', 'PUBLIC LEGAL SUMMARY');
        $linkedDraftLegalUnit = $this->createLegalUnit($legalAct->getKey(), 'art-2', 'Art. 2', 'Projektowana zasada', 'DRAFT LEGAL SUMMARY');
        $unlinkedLegalUnit = $this->createLegalUnit($legalAct->getKey(), 'art-3', 'Art. 3', 'Niepowiązana zasada', 'UNLINKED LEGAL SUMMARY');

        $publishedLegalPage = $this->createLegalPage($legalTopic->getKey(), 'pierwszenstwo-publiczne', 'Publiczna strona przepisu', LegalContentPage::STATUS_PUBLISHED, now()->subHour());
        $publishedLegalPage->legalUnits()->attach($linkedLegalUnit->getKey(), ['relation_type' => 'direct_basis', 'sort_order' => 0]);

        $draftLegalPage = $this->createLegalPage($legalTopic->getKey(), 'pierwszenstwo-szkic', 'Szkic strony przepisu', LegalContentPage::STATUS_DRAFT, null);
        $draftLegalPage->legalUnits()->attach($linkedDraftLegalUnit->getKey(), ['relation_type' => 'direct_basis', 'sort_order' => 0]);

        $unlinkedLegalPage = $this->createLegalPage($legalTopic->getKey(), 'pierwszenstwo-niepowiazane', 'Niepowiązana strona przepisu', LegalContentPage::STATUS_PUBLISHED, now()->subHour());
        $unlinkedLegalPage->legalUnits()->attach($unlinkedLegalUnit->getKey(), ['relation_type' => 'direct_basis', 'sort_order' => 0]);

        $signAuthor = ContentAuthor::factory()->published()->create();
        $signCategory = TrafficSignCategory::factory()->published()->create();
        $linkedSign = TrafficSign::factory()->published()->create([
            'content_author_id' => $signAuthor->getKey(),
            'traffic_sign_category_id' => $signCategory->getKey(),
            'code' => 'A-7',
            'slug' => 'a-7-ustap-pierwszenstwa-bridge',
            'name' => 'Ustąp pierwszeństwa — bridge',
        ]);
        $linkedHiddenSign = TrafficSign::factory()->create([
            'content_author_id' => $signAuthor->getKey(),
            'traffic_sign_category_id' => $signCategory->getKey(),
            'code' => 'B-1',
            'slug' => 'b-1-ukryty-bridge',
            'name' => 'Ukryty znak bridge',
        ]);
        $linkedRelatedSign = TrafficSign::factory()->published()->create([
            'content_author_id' => $signAuthor->getKey(),
            'traffic_sign_category_id' => $signCategory->getKey(),
            'code' => 'C-1',
            'slug' => 'c-1-powiazany-bridge',
            'name' => 'Powiązany znak bridge',
        ]);
        $unlinkedSign = TrafficSign::factory()->published()->create([
            'content_author_id' => $signAuthor->getKey(),
            'traffic_sign_category_id' => $signCategory->getKey(),
            'code' => 'D-1',
            'slug' => 'd-1-niepowiazany-bridge',
            'name' => 'Niepowiązany znak bridge',
        ]);

        $article = ContentArticle::factory()->published()->state([
            'title' => 'Artykuł z Product Bridge',
            'slug' => 'artykul-z-product-bridge',
            'body_blocks' => NewsroomBodyContract::normalize([
                [
                    'type' => NewsroomBodyContract::BLOCK_QUESTION_GROUP,
                    'data' => [
                        'question_ids' => [
                            $linkedQuestion->getKey(),
                            $linkedInactiveQuestion->getKey(),
                            $unlinkedQuestion->getKey(),
                        ],
                    ],
                ],
                ['type' => NewsroomBodyContract::BLOCK_LEGAL_REFERENCE, 'data' => ['legal_unit_id' => $linkedLegalUnit->getKey()]],
                ['type' => NewsroomBodyContract::BLOCK_LEGAL_REFERENCE, 'data' => ['legal_unit_id' => $linkedDraftLegalUnit->getKey()]],
                ['type' => NewsroomBodyContract::BLOCK_LEGAL_REFERENCE, 'data' => ['legal_unit_id' => $unlinkedLegalUnit->getKey()]],
                [
                    'type' => NewsroomBodyContract::BLOCK_TRAFFIC_SIGN_GROUP,
                    'data' => [
                        'traffic_sign_ids' => [
                            $linkedSign->getKey(),
                            $linkedHiddenSign->getKey(),
                            $linkedRelatedSign->getKey(),
                            $unlinkedSign->getKey(),
                        ],
                    ],
                ],
                ['type' => NewsroomBodyContract::BLOCK_PRODUCT_CTA, 'data' => ['kind' => 'test']],
                ['type' => NewsroomBodyContract::BLOCK_PRODUCT_CTA, 'data' => ['kind' => 'related_questions']],
                ['type' => NewsroomBodyContract::BLOCK_PRODUCT_CTA, 'data' => ['kind' => 'learning']],
            ]),
        ])->create();

        $article->questions()->attach([
            $linkedQuestion->getKey() => ['relation_type' => 'direct', 'sort_order' => 0, 'note' => 'QUESTION INTERNAL NOTE'],
            $linkedInactiveQuestion->getKey() => ['relation_type' => 'direct', 'sort_order' => 1, 'note' => null],
        ]);
        $article->legalUnits()->attach([
            $linkedLegalUnit->getKey() => ['relation_type' => 'direct_basis', 'sort_order' => 0, 'note' => 'LEGAL INTERNAL NOTE'],
            $linkedDraftLegalUnit->getKey() => ['relation_type' => 'direct_basis', 'sort_order' => 1, 'note' => null],
        ]);
        $article->trafficSigns()->attach([
            $linkedSign->getKey() => ['relation_type' => 'direct', 'sort_order' => 0],
            $linkedHiddenSign->getKey() => ['relation_type' => 'direct', 'sort_order' => 1],
            $linkedRelatedSign->getKey() => ['relation_type' => 'related', 'sort_order' => 2],
        ]);

        $response = $this->get(route('public.news.show', $article->slug));

        $response->assertOk()
            ->assertSee('Sprawdź, czy to umiesz')
            ->assertSee($linkedQuestion->prompt)
            ->assertSee(app(PublicQuestionCatalogService::class)->questionUrl($linkedQuestion), false)
            ->assertDontSee($linkedInactiveQuestion->prompt)
            ->assertDontSee($unlinkedQuestion->prompt)
            ->assertSee('PoRD test · Art. 1')
            ->assertSee('PUBLIC LEGAL SUMMARY')
            ->assertSee(route('public.regulations.show', $publishedLegalPage->slug), false)
            ->assertDontSee('DRAFT LEGAL SUMMARY')
            ->assertDontSee('UNLINKED LEGAL SUMMARY')
            ->assertDontSee('SECRET OFFICIAL EXCERPT')
            ->assertDontSee('QUESTION INTERNAL NOTE')
            ->assertDontSee('LEGAL INTERNAL NOTE')
            ->assertSee($linkedSign->publicTitle())
            ->assertSee(route('traffic-signs.show', $linkedSign->slug), false)
            ->assertDontSee($linkedRelatedSign->publicTitle())
            ->assertDontSee($linkedHiddenSign->publicTitle())
            ->assertDontSee($unlinkedSign->publicTitle())
            ->assertSee('Sprawdź się w teście')
            ->assertSee(route('public.tests'), false)
            ->assertSee('Zobacz oficjalną bazę pytań')
            ->assertSee(route('public.questions.hub'), false)
            ->assertSee('Przejdź do nauki')
            ->assertSee(route('session.index'), false);
    }

    private function createLegalUnit(
        int $legalActId,
        string $slug,
        string $label,
        string $title,
        string $summary,
    ): LegalUnit {
        return LegalUnit::query()->create([
            'legal_act_id' => $legalActId,
            'type' => 'article',
            'label' => $label,
            'slug' => $slug,
            'canonical_path' => $slug,
            'title' => $title,
            'summary' => $summary,
            'official_excerpt' => 'SECRET OFFICIAL EXCERPT',
            'source_url' => 'https://example.com/'.$slug,
            'status' => LegalUnit::STATUS_VERIFIED,
        ]);
    }

    private function createLegalPage(
        int $legalTopicId,
        string $slug,
        string $title,
        string $status,
        mixed $publishedAt,
    ): LegalContentPage {
        return LegalContentPage::query()->create([
            'legal_topic_id' => $legalTopicId,
            'slug' => $slug,
            'title' => $title,
            'summary' => 'Publiczny opis strony '.$slug,
            'status' => $status,
            'published_at' => $publishedAt,
        ]);
    }
}
