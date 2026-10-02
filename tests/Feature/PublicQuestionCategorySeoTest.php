<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryLabel;

test('category editorial uses real eligible questions and category specific topic labels', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $otherCategory = LicenseCategory::factory()->categoryC()->create();
    $topic = QuestionTopic::factory()->create(['name' => 'Zagadnienie wspólne']);
    QuestionTopicCategoryLabel::factory()->create([
        'license_category_id' => $category->id,
        'question_topic_id' => $topic->id,
        'display_name' => 'Zasady dla kierowcy samochodu osobowego',
        'is_active' => true,
    ]);
    $question = Question::factory()->for($category, 'licenseCategory')->booleanType()->create([
        'external_id' => '81001',
        'question_topic_id' => $topic->id,
        'prompt' => 'Czy <strong>musisz</strong> ustąpić pierwszeństwa?',
    ]);
    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '81002', 'question_topic_id' => $topic->id,
    ]);
    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '81003', 'question_topic_id' => $topic->id, 'is_active' => false,
        'prompt' => 'Nieaktywne pytanie nie może być przykładem.',
    ]);
    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '81004', 'question_topic_id' => $topic->id,
        'delivery_issue' => Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA,
        'prompt' => 'Pytanie niedostępne do wyświetlenia.',
    ]);
    Question::factory()->for($otherCategory, 'licenseCategory')->create([
        'external_id' => '81005', 'question_topic_id' => $topic->id,
        'prompt' => 'Pytanie tylko z kategorii C.',
    ]);

    $response = $this->get(route('public.questions.category', 'b'));
    $response->assertOk()
        ->assertSeeText('Pytania na prawo jazdy kat. B — oficjalna baza')
        ->assertSeeText('Zasady dla kierowcy samochodu osobowego')
        ->assertSeeText('Czy musisz ustąpić pierwszeństwa?')
        ->assertSee('/oficjalna-baza-pytan-na-prawo-jazdy/b/pytanie/81001/', false)
        ->assertDontSeeText('Nieaktywne pytanie nie może być przykładem.')
        ->assertDontSeeText('Pytanie niedostępne do wyświetlenia.')
        ->assertDontSeeText('Pytanie tylko z kategorii C.');
    expect($response->viewData('categoryContent')['total'])->toBe(2)
        ->and($response->viewData('categoryContent')['topic_count'])->toBe(1)
        ->and($response->viewData('categoryContent')['topics'][0]['count'])->toBe(2);
});

test('category search keeps full counts but excludes editorial and indexing', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $topic = QuestionTopic::factory()->create();
    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '82001', 'question_topic_id' => $topic->id, 'prompt' => 'Widoczne pytanie.',
    ]);
    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '82002', 'question_topic_id' => $topic->id, 'prompt' => 'Niepasujące pytanie.',
    ]);
    $response = $this->get(route('public.questions.category', ['categorySlug' => 'b', 'q' => '82001']));
    $response->assertOk()->assertSeeText('Wyniki wyszukiwania')
        ->assertSeeText('Widoczne pytanie.')->assertDontSeeText('Niepasujące pytanie.')
        ->assertDontSee('id="zagadnienia"', false)
        ->assertSee('content="noindex,follow"', false);
    expect($response->viewData('categoryContent')['total'])->toBe(2)
        ->and($response->viewData('questions')->total())->toBe(1)
        ->and($response->viewData('meta')['canonical'])->toBe(route('public.questions.category', 'b'))
        ->and($response->viewData('meta')['description'])->toContain('Liczba pytań: 2');
});

test('category pagination has its own canonical schema and no repeated editorial', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    Question::factory()->count(21)->for($category, 'licenseCategory')->create();
    $response = $this->get(route('public.questions.category', ['categorySlug' => 'b', 'page' => 2]));
    $response->assertOk()->assertSeeText('Pytania egzaminacyjne kategorii B — strona 2')
        ->assertSee('aria-label="Poprzednia strona"', false)
        ->assertSee('aria-current="page"', false)
        ->assertDontSee('id="o-bazie"', false);
    $canonical = route('public.questions.category', 'b').'?page=2';
    $graph = collect($response->viewData('structuredData')['@graph']);
    expect($response->viewData('meta')['canonical'])->toBe($canonical)
        ->and($graph->firstWhere('@type', 'CollectionPage')['url'])->toBe($canonical)
        ->and($graph->firstWhere('@type', 'ItemList')['@id'])->toBe($canonical.'#questions')
        ->and($response->viewData('categoryContent')['total'])->toBe(21);
});

test('shared seo template works for every visible driving license category without invented topics', function () {
    foreach (['A', 'A1', 'A2', 'AM', 'B', 'B1', 'C', 'C1', 'D', 'D1', 'T'] as $code) {
        $category = LicenseCategory::factory()->withCode($code)->create();
        Question::factory()->for($category, 'licenseCategory')->create(['external_id' => '83001']);
        $response = $this->get(route('public.questions.category', $category->slug));
        $response->assertOk()->assertSeeText('Pytania na prawo jazdy kat. '.$code.' — oficjalna baza')
            ->assertSeeText('Pytania i odpowiedzi o bazie kat. '.$code)
            ->assertDontSee('id="zagadnienia"', false);
        expect($response->viewData('categoryContent')['total'])->toBe(1)
            ->and($response->viewData('categoryContent')['topics'])->toBe([]);
    }
});
