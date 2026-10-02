<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use Illuminate\Support\Str;

test('question layout keeps the full explanation and all reading controls in one root', function () {
    $category = LicenseCategory::factory()->withCode('B1')->create();
    $question = Question::factory()->booleanType()->for($category, 'licenseCategory')->create([
        'external_id' => '91001', 'prompt' => 'Czy masz obowiązek zatrzymać pojazd?', 'correct_answer' => 'a',
    ]);
    QuestionPublicExplanation::factory()->published()->create([
        'external_id' => '91001',
        'body' => '<p>Całe wyjaśnienie pozostaje dostępne.</p><p><strong>Zasada do zapamiętania:</strong> zachowaj ostrożność.</p>',
        'dont_confuse_with' => 'Nie myl tych sytuacji.',
        'exam_trap' => 'Zwróć uwagę na polecenie.',
        'common_mistakes' => [['title' => 'Pomijanie polecenia', 'explanation' => 'Przeczytaj całą treść.']],
    ]);
    $response = $this->get(route('public.questions.category.show', [
        'categorySlug' => 'b1', 'externalId' => '91001', 'slug' => Str::slug($question->prompt),
    ]));
    $response->assertOk()->assertSeeText('TAK')->assertSeeText('Całe wyjaśnienie pozostaje dostępne.')
        ->assertSeeText('Zasada do zapamiętania:')->assertSeeText('Nie myl tych sytuacji.')
        ->assertSeeText('Zwróć uwagę na polecenie.')->assertSeeText('Pomijanie polecenia');
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//*[@data-lesson-audio-root]')->length)->toBe(1)
        ->and($xpath->query('//*[@data-lesson-audio-root]//*[@data-lesson-audio-section]')->length)->toBe(4)
        ->and($xpath->query('//*[@data-lesson-audio-root]//*[@data-lesson-audio-section-button]')->length)->toBe(4)
        ->and($xpath->query('//*[contains(@class, "question-detail__explanation")]//*[@data-public-explanation-dont-confuse]')->length)->toBe(0);
    $html = $response->getContent();
    expect(strpos($html, 'aria-label="Nawigacja po pytaniach"'))->toBeLessThan(strpos($html, 'id="related-questions-heading"'));
});

test('question layout shows the complete correct choice and handles missing explanation without fabricated content', function () {
    $category = LicenseCategory::factory()->categoryB()->create();
    $question = Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '91002', 'prompt' => 'Którą odpowiedź wybierzesz?',
        'correct_answer' => 'b', 'option_b' => 'Pełna treść poprawnej odpowiedzi.', 'explanation' => null,
    ]);
    $response = $this->get(route('public.questions.category.show', [
        'categorySlug' => 'b', 'externalId' => '91002', 'slug' => Str::slug($question->prompt),
    ]));
    $response->assertOk()->assertSee('question-detail__correct-text', false)
        ->assertSeeText('Pełna treść poprawnej odpowiedzi.')->assertSeeText('To pytanie nie ma jeszcze osobnego rozwinięcia.')
        ->assertDontSee('question-detail__pitfalls', false);
});
