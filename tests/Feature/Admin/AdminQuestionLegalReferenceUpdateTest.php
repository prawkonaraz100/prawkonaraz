<?php

use App\Models\AuditLog;
use App\Models\LegalAct;
use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use App\Models\User;

test('admin can create legal reference without a topic or linked article', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'external_id' => '12345',
    ]);
    $siblingQuestion = Question::factory()->create([
        'external_id' => 'pj360:12345',
    ]);
    [$legalUnit] = createLegalBasisFixture();

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.legal-reference.update', $question), [
            'public_note' => 'To pytanie wymaga zastosowania obowiązku wobec pasażerów tramwaju.',
            'legal_unit_id' => $legalUnit->getKey(),
            'legal_topic_id' => null,
            'legal_content_page_id' => null,
        ])
        ->assertOk()
        ->assertJsonPath('data.reference.public_note', 'To pytanie wymaga zastosowania obowiązku wobec pasażerów tramwaju.')
        ->assertJsonPath('data.reference.legal_unit_id', $legalUnit->getKey())
        ->assertJsonPath('data.reference.legal_topic_id', null)
        ->assertJsonPath('data.reference.legal_content_page_id', null)
        ->assertJsonPath('data.reference.status', QuestionLegalReference::STATUS_VERIFIED)
        ->assertJsonPath('data.reference.verified_label', today()->format('d.m.Y'));

    foreach ([$question, $siblingQuestion] as $groupQuestion) {
        $reference = QuestionLegalReference::query()
            ->where('question_id', $groupQuestion->getKey())
            ->where('legal_unit_id', $legalUnit->getKey())
            ->whereNull('legal_topic_id')
            ->firstOrFail();

        expect($reference->legal_topic_id)->toBeNull();
        expect($reference->legal_content_page_id)->toBeNull();
        expect($reference->status)->toBe(QuestionLegalReference::STATUS_VERIFIED);
        expect($reference->verified_at?->toDateString())->toBe(today()->toDateString());
    }

    $auditLog = AuditLog::query()
        ->where('action', 'admin.question_legal_reference.updated')
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog?->actor_user_id)->toBe($admin->getKey());
    expect($auditLog?->metadata)->toMatchArray([
        'source' => 'public_question_inline',
        'question_id' => $question->getKey(),
        'external_id' => '12345',
        'group_question_count' => 2,
        'legal_unit_id' => $legalUnit->getKey(),
        'legal_topic_id' => null,
        'legal_content_page_id' => null,
    ]);
});

test('admin can link a published legal article later', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'external_id' => '22334',
    ]);
    [$legalUnit, $legalTopic] = createLegalBasisFixture();
    $page = LegalContentPage::query()->create([
        'legal_topic_id' => $legalTopic->getKey(),
        'slug' => 'tramwaje-i-przystanki-test',
        'title' => 'Tramwaje i przystanki',
        'published_at' => now()->subDay(),
        'status' => LegalContentPage::STATUS_PUBLISHED,
    ]);
    $reference = QuestionLegalReference::query()->create([
        'question_id' => $question->getKey(),
        'legal_unit_id' => $legalUnit->getKey(),
        'legal_topic_id' => $legalTopic->getKey(),
        'legal_content_page_id' => null,
        'public_note' => 'Stary opis.',
        'status' => QuestionLegalReference::STATUS_VERIFIED,
        'verified_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.legal-reference.update', $question), [
            'reference_id' => $reference->getKey(),
            'public_note' => 'Opis po podpięciu artykułu.',
            'legal_unit_id' => $legalUnit->getKey(),
            'legal_topic_id' => $legalTopic->getKey(),
            'legal_content_page_id' => $page->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.reference.legal_content_page_id', $page->getKey());

    expect($reference->fresh()?->legal_content_page_id)->toBe($page->getKey());
    expect($reference->fresh()?->public_note)->toBe('Opis po podpięciu artykułu.');
});

test('non admin cannot update legal reference', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();
    [$legalUnit, $legalTopic] = createLegalBasisFixture();

    $this->actingAs($user)
        ->patchJson(route('api.v1.admin.questions.legal-reference.update', $question), [
            'public_note' => 'Nie powinno się zapisać.',
            'legal_unit_id' => $legalUnit->getKey(),
            'legal_topic_id' => $legalTopic->getKey(),
        ])
        ->assertForbidden();

    expect(QuestionLegalReference::query()->exists())->toBeFalse();
});

test('admin can save legal reference with blank optional note', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();
    [$legalUnit, $legalTopic] = createLegalBasisFixture();

    $this->actingAs($admin)
        ->patchJson(route('api.v1.admin.questions.legal-reference.update', $question), [
            'public_note' => '   ',
            'legal_unit_id' => $legalUnit->getKey(),
            'legal_topic_id' => $legalTopic->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.reference.public_note', null)
        ->assertJsonPath('data.reference.legal_unit_id', $legalUnit->getKey())
        ->assertJsonPath('data.reference.legal_topic_id', $legalTopic->getKey());

    $reference = QuestionLegalReference::query()
        ->where('question_id', $question->getKey())
        ->where('legal_unit_id', $legalUnit->getKey())
        ->where('legal_topic_id', $legalTopic->getKey())
        ->firstOrFail();

    expect($reference->public_note)->toBeNull();
});

/**
 * @return array{0: LegalUnit, 1: LegalTopic}
 */
function createLegalBasisFixture(): array
{
    $legalAct = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym-test',
        'title' => 'Prawo o ruchu drogowym',
        'short_title' => 'Prawo o ruchu drogowym',
        'source_url' => 'https://example.test/pord',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);

    $legalUnit = LegalUnit::query()->create([
        'legal_act_id' => $legalAct->getKey(),
        'type' => 'article',
        'label' => 'art. 26 ust. 6',
        'slug' => 'art-26-ust-6',
        'title' => 'Obowiązki kierującego przy przystankach tramwajowych',
        'source_url' => 'https://example.test/pord/art-26',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);

    $legalTopic = LegalTopic::query()->create([
        'slug' => 'tramwaje-i-przystanki-test',
        'title' => 'Tramwaje i przystanki',
        'status' => LegalTopic::STATUS_PUBLISHED,
        'published_at' => now()->subDay(),
    ]);

    return [$legalUnit, $legalTopic];
}
