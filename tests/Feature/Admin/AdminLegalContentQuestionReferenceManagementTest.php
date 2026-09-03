<?php

use App\Models\AuditLog;
use App\Models\LegalAct;
use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use App\Models\User;
use Database\Seeders\LegalTrustLayerMvpSeeder;

test('question management controls on a legal article are visible only to administrators', function () {
    [$page] = createLegalArticleQuestionManagementFixture();
    $admin = User::factory()->admin()->create();

    $this->get(route('public.regulations.show', $page))
        ->assertOk()
        ->assertDontSeeText('Dodaj pytania')
        ->assertDontSee('data-legal-question-manager', false);

    $this->actingAs($admin)
        ->get(route('public.regulations.show', $page))
        ->assertOk()
        ->assertSeeText('Dodaj pytania')
        ->assertSee('data-legal-question-manager', false)
        ->assertSee(route('api.v1.admin.legal-content-pages.question-references.index', $page), false);
});

test('administrator can search canonical questions for a legal article', function () {
    [$page] = createLegalArticleQuestionManagementFixture();
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '8150',
        'prompt' => 'Czy w tej sytuacji należy ustąpić pierwszeństwa pieszemu?',
    ]);
    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => 'pj360:8150',
        'prompt' => 'Czy w tej sytuacji należy ustąpić pierwszeństwa pieszemu?',
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('api.v1.admin.legal-content-pages.question-references.index', [
            'legalContentPage' => $page,
            'q' => '8150',
        ]))
        ->assertOk();

    expect($response->json('data.questions'))->toHaveCount(1)
        ->and($response->json('data.questions.0.external_id'))->toBe('8150')
        ->and($response->json('data.questions.0.already_attached'))->toBeFalse();
});

test('non administrator cannot use legal article question management endpoints', function () {
    [$page, $legalUnit] = createLegalArticleQuestionManagementFixture();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.admin.legal-content-pages.question-references.index', [
            'legalContentPage' => $page,
            'q' => 'pieszy',
        ]))
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('api.v1.admin.legal-content-pages.question-references.store', $page), [
            'external_ids' => ['8150'],
            'legal_unit_id' => $legalUnit->getKey(),
        ])
        ->assertForbidden();
});

test('administrator can assign a canonical question group directly from a legal article', function () {
    [$page, $legalUnit] = createLegalArticleQuestionManagementFixture();
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '8151',
        'prompt' => 'Czy masz obowiązek zachować szczególną ostrożność?',
    ]);
    $siblingQuestion = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => 'pj360:8151',
        'prompt' => 'Czy masz obowiązek zachować szczególną ostrożność?',
    ]);

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.legal-content-pages.question-references.store', $page), [
            'external_ids' => ['8151'],
            'legal_unit_id' => $legalUnit->getKey(),
            'public_notes' => [
                '8151' => 'Pytanie sprawdza obowiązek zachowania szczególnej ostrożności.',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.assignment.external_ids.0', '8151')
        ->assertJsonPath('data.assignment.question_rows', 2)
        ->assertJsonPath('data.assignment.created', 2);

    $references = QuestionLegalReference::query()
        ->whereIn('question_id', [$question->getKey(), $siblingQuestion->getKey()])
        ->get();

    expect($references)->toHaveCount(2)
        ->and($references->every(
            fn (QuestionLegalReference $reference): bool => $reference->legal_content_page_id === $page->getKey()
                && $reference->assignment_source === QuestionLegalReference::SOURCE_MANUAL
                && $reference->status === QuestionLegalReference::STATUS_VERIFIED
                && $reference->public_note === 'Pytanie sprawdza obowiązek zachowania szczególnej ostrożności.',
        ))->toBeTrue();

    $auditLog = AuditLog::query()
        ->where('action', 'admin.legal_content_question_references.assigned')
        ->firstOrFail();

    expect($auditLog->actor_user_id)->toBe($admin->getKey())
        ->and($auditLog->metadata['external_ids'])->toBe(['8151'])
        ->and($auditLog->metadata['legal_content_page_slug'])->toBe($page->slug);

    $this->get(route('public.regulations.show', $page))
        ->assertOk()
        ->assertSeeText('Pytanie 8151')
        ->assertSeeText('Czy masz obowiązek zachować szczególną ostrożność?');
});

test('administrator cannot assign a legal unit that is not linked to the article', function () {
    [$page] = createLegalArticleQuestionManagementFixture();
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '8152',
    ]);
    $otherAct = LegalAct::query()->create([
        'slug' => 'inny-akt-test',
        'title' => 'Inny akt',
        'source_url' => 'https://example.test/inny-akt',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $otherUnit = LegalUnit::query()->create([
        'legal_act_id' => $otherAct->getKey(),
        'type' => 'article',
        'label' => 'art. 99',
        'slug' => 'art-99',
        'title' => 'Inna podstawa',
        'source_url' => 'https://example.test/inny-akt/art-99',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.legal-content-pages.question-references.store', $page), [
            'external_ids' => ['8152'],
            'legal_unit_id' => $otherUnit->getKey(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('legal_unit_id');

    expect($question->legalReferences()->exists())->toBeFalse();
});

test('administrator can assign a precise descendant unit from the article legal context', function () {
    [$page, $legalUnit] = createLegalArticleQuestionManagementFixture();
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '8154',
    ]);
    $preciseUnit = LegalUnit::query()->create([
        'legal_act_id' => $legalUnit->legal_act_id,
        'parent_legal_unit_id' => $legalUnit->getKey(),
        'type' => 'paragraph',
        'label' => 'art. 26 ust. 1 pkt 1',
        'slug' => 'art-26-ust-1-pkt-1-management-test',
        'title' => 'Dokładna jednostka podrzędna',
        'source_url' => 'https://example.test/pord/art-26',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);

    $this->actingAs($admin)
        ->get(route('public.regulations.show', $page))
        ->assertOk()
        ->assertSeeText('art. 26 ust. 1 pkt 1 - Dokładna jednostka podrzędna');

    $this->actingAs($admin)
        ->postJson(route('api.v1.admin.legal-content-pages.question-references.store', $page), [
            'external_ids' => ['8154'],
            'legal_unit_id' => $preciseUnit->getKey(),
        ])
        ->assertOk();

    expect($question->legalReferences()->firstOrFail()->legal_unit_id)->toBe($preciseUnit->getKey());
});

test('administrator can remove all variants of a question from a legal article', function () {
    [$page, $legalUnit] = createLegalArticleQuestionManagementFixture();
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->categoryB()->create();
    $questions = collect([
        Question::factory()->for($category, 'licenseCategory')->create(['external_id' => '8153']),
        Question::factory()->for($category, 'licenseCategory')->create(['external_id' => 'pj360:8153']),
    ]);
    $references = $questions->map(fn (Question $question): QuestionLegalReference => QuestionLegalReference::query()->create([
        'question_id' => $question->getKey(),
        'legal_unit_id' => $legalUnit->getKey(),
        'legal_topic_id' => $page->legal_topic_id,
        'legal_content_page_id' => $page->getKey(),
        'public_note' => 'Powiązanie do usunięcia.',
        'assignment_source' => QuestionLegalReference::SOURCE_SEED,
        'status' => QuestionLegalReference::STATUS_VERIFIED,
        'verified_at' => now(),
    ]));

    $this->actingAs($admin)
        ->deleteJson(route('api.v1.admin.legal-content-pages.question-references.destroy', [
            'legalContentPage' => $page,
            'questionLegalReference' => $references->first(),
        ]))
        ->assertOk()
        ->assertJsonPath('data.removal.external_id', '8153')
        ->assertJsonPath('data.removal.affected', 2);

    $references->each(function (QuestionLegalReference $reference): void {
        $reference->refresh();

        expect($reference->assignment_source)->toBe(QuestionLegalReference::SOURCE_MANUAL)
            ->and($reference->status)->toBe(QuestionLegalReference::STATUS_REJECTED)
            ->and($reference->verified_at)->toBeNull();
    });

    $this->get(route('public.regulations.show', $page))
        ->assertOk()
        ->assertDontSeeText('Pytanie 8153');
});

test('legal trust seeder does not overwrite a manually managed question reference', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '10249',
    ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    $reference = QuestionLegalReference::query()
        ->where('question_id', $question->getKey())
        ->firstOrFail();

    $reference->update([
        'assignment_source' => QuestionLegalReference::SOURCE_MANUAL,
        'public_note' => 'Ręcznie poprawiona treść, której seeder nie może nadpisać.',
    ]);

    $this->seed(LegalTrustLayerMvpSeeder::class);

    expect($reference->fresh()?->assignment_source)->toBe(QuestionLegalReference::SOURCE_MANUAL)
        ->and($reference->fresh()?->public_note)->toBe('Ręcznie poprawiona treść, której seeder nie może nadpisać.');
});

/**
 * @return array{0: LegalContentPage, 1: LegalUnit}
 */
function createLegalArticleQuestionManagementFixture(): array
{
    $legalAct = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym-management-test',
        'title' => 'Prawo o ruchu drogowym',
        'short_title' => 'Prawo o ruchu drogowym',
        'source_url' => 'https://example.test/pord',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $legalUnit = LegalUnit::query()->create([
        'legal_act_id' => $legalAct->getKey(),
        'type' => 'article',
        'label' => 'art. 26 ust. 1',
        'slug' => 'art-26-ust-1-management-test',
        'title' => 'Obowiązki wobec pieszego',
        'source_url' => 'https://example.test/pord/art-26',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);
    $topic = LegalTopic::query()->create([
        'slug' => 'piesi-management-test',
        'title' => 'Piesi',
        'status' => LegalTopic::STATUS_PUBLISHED,
        'published_at' => now()->subDay(),
    ]);
    $page = LegalContentPage::query()->create([
        'legal_topic_id' => $topic->getKey(),
        'slug' => 'piesi-management-test',
        'title' => 'Piesi i przejścia test',
        'published_at' => now()->subDay(),
        'status' => LegalContentPage::STATUS_PUBLISHED,
    ]);

    $page->legalUnits()->attach($legalUnit->getKey(), [
        'relation_type' => 'direct_basis',
        'sort_order' => 10,
    ]);

    return [$page->fresh('legalUnits.legalAct'), $legalUnit];
}
