<?php

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\ContentAuthor;
use App\Models\LegalAct;
use App\Models\LegalUnit;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Models\QuestionExplanationAsset;
use App\Models\QuestionLegalReference;
use App\Models\QuestionMedia;
use App\Models\QuestionPublicExplanation;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\User;
use App\Support\PublicQuestionSignReferenceService;
use App\Support\QuestionAudioExportManifestBuilder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    config()->set('app.name', 'prawkonaraz.pl');
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('content.organization.name', 'PrawkoNaRaz');
    config()->set('content.organization.legal_name', null);
    config()->set('content.organization.email', 'kontakt@prawkonaraz.pl');
    config()->set('content.organization.logo_url', 'https://prawkonaraz.pl/favicon.png');
    config()->set('content.organization.logo_width', 256);
    config()->set('content.organization.logo_height', 256);
    config()->set('filesystems.disks.public.url', 'https://prawkonaraz.pl/storage');
    config()->set('filesystems.disks.media_local.url', 'https://prawkonaraz.pl/storage-bulk');
    config()->set('media.public_base_url', 'https://prawkonaraz.pl/storage');
    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');
});

afterEach(function (): void {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

/**
 * @return list<array<string, mixed>>
 */
function questionDatabaseJsonLdScripts(TestResponse $response): array
{
    preg_match_all(
        '/<script type="application\/ld\+json">(.+?)<\/script>/s',
        $response->getContent(),
        $matches,
    );

    return collect($matches[1] ?? [])
        ->map(fn (string $json): mixed => json_decode($json, true))
        ->filter(fn (mixed $schema): bool => is_array($schema))
        ->values()
        ->all();
}

/**
 * @return list<array<string, mixed>>
 */
function questionDatabaseJsonLdGraph(TestResponse $response): array
{
    $scripts = questionDatabaseJsonLdScripts($response);

    expect($scripts)->toHaveCount(1);
    expect($scripts[0]['@context'] ?? null)->toBe('https://schema.org');
    expect($scripts[0]['@graph'] ?? null)->toBeArray();

    return $scripts[0]['@graph'];
}

/**
 * @return array<string, mixed>|null
 */
function questionDatabaseGraphNode(TestResponse $response, string $type): ?array
{
    return collect(questionDatabaseJsonLdGraph($response))
        ->first(fn (mixed $node): bool => is_array($node) && ($node['@type'] ?? null) === $type);
}

/**
 * @return list<string>
 */
function questionDatabaseGraphDuplicateIds(TestResponse $response): array
{
    return collect(questionDatabaseJsonLdGraph($response))
        ->pluck('@id')
        ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
        ->countBy()
        ->filter(fn (int $count): bool => $count > 1)
        ->keys()
        ->values()
        ->all();
}

function questionDatabasePlainText(TestResponse $response): string
{
    return html_entity_decode(strip_tags($response->getContent()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * @return array<string, mixed>
 */
function questionDatabaseAttachGeneratedAudio(Question $question, ?float $durationSeconds = 3.2): array
{
    $item = app(QuestionAudioExportManifestBuilder::class)->build([
        'external_ids' => [(string) $question->external_id],
    ])['items'][0];

    Storage::disk('media_local')->put($item['target_storage_path'], 'fake-mp3');

    QuestionAudioAsset::query()->create([
        'question_id' => $item['question_id'],
        'external_id' => $item['external_id'],
        'asset_key' => $item['asset_key'],
        'content_scope' => $item['content_scope'],
        'audio_type' => $item['audio_type'],
        'locale' => $item['locale'],
        'source_text' => $item['source_text'],
        'source_text_hash' => $item['source_text_hash'],
        'storage_disk' => 'media_local',
        'storage_path' => $item['target_storage_path'],
        'duration_seconds' => $durationSeconds,
        'encoding_format' => 'audio/mpeg',
        'bytes' => 8,
        'voice_provider' => $item['voice_provider'],
        'voice_id' => $item['voice_id'],
        'model_id' => $item['model_id'],
        'generation_version' => $item['generation_version'],
        'status' => QuestionAudioAsset::STATUS_GENERATED,
        'generated_at' => now(),
    ]);

    return $item;
}

test('public question database hub renders grouped category cards', function () {
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 2,
    ]);

    Question::factory()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
        ]);
    Question::factory()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => 'pj360:100',
            'prompt' => 'Czy w tej sytuacji powinieneś oczekiwać następnego sygnału?',
        ]);
    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '469',
            'prompt' => 'Czy kierujący samochodem powinien zachować szczególną ostrożność?',
        ]);

    $this->get(route('public.questions.hub'))
        ->assertOk()
        ->assertSeeText('Oficjalna baza pytań na prawo jazdy '.now()->year)
        ->assertSeeText('Kategorie prawa jazdy')
        ->assertSeeText('Najczęściej wybierane')
        ->assertSeeText('Motocykle')
        ->assertSeeText('Kategoria A')
        ->assertSeeText('Kategoria B')
        ->assertSeeText('Sprawdź')
        ->assertSeeText('2 pytań')
        ->assertSeeText('1 pytań')
        ->assertDontSeeText('Lista pytań')
        ->assertDontSeeText('Pytanie 99')
        ->assertDontSeeText('Pytanie 100')
        ->assertDontSeeText('pj360:100')
        ->assertDontSeeText('Pokazujemy 2 przykładowych pytań z 2');
});

test('public question database hub renders a single schema graph foundation', function () {
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 2,
    ]);

    Question::factory()
        ->for($categoryA, 'licenseCategory')
        ->create(['external_id' => '99']);
    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create(['external_id' => '469']);

    $response = $this->get(route('public.questions.hub'));
    $graph = questionDatabaseJsonLdGraph($response);
    $organization = questionDatabaseGraphNode($response, 'Organization');
    $website = questionDatabaseGraphNode($response, 'WebSite');
    $breadcrumb = questionDatabaseGraphNode($response, 'BreadcrumbList');
    $collectionPage = questionDatabaseGraphNode($response, 'CollectionPage');
    $dataset = questionDatabaseGraphNode($response, 'Dataset');
    $itemList = questionDatabaseGraphNode($response, 'ItemList');

    expect($organization)->toBeArray()
        ->and($website)->toBeArray()
        ->and($breadcrumb)->toBeArray()
        ->and($collectionPage)->toBeArray()
        ->and($dataset)->toBeArray()
        ->and($itemList)->toBeArray()
        ->and(questionDatabaseGraphDuplicateIds($response))->toBe([])
        ->and($organization['@id'])->toBe('https://prawkonaraz.pl/#organization')
        ->and($organization['name'])->toBe('PrawkoNaRaz')
        ->and(array_key_exists('legalName', $organization))->toBeFalse()
        ->and($website['@id'])->toBe('https://prawkonaraz.pl/#website')
        ->and($website['name'])->toBe('PrawkoNaRaz')
        ->and(data_get($website, 'potentialAction.target'))->toBe('https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy?q={search_term_string}')
        ->and($collectionPage['@id'])->toBe(route('public.questions.hub').'#webpage')
        ->and($collectionPage['isPartOf'])->toBe(['@id' => 'https://prawkonaraz.pl/#website'])
        ->and($collectionPage['breadcrumb'])->toBe(['@id' => route('public.questions.hub').'#breadcrumb'])
        ->and($collectionPage['mainEntity'])->toBe(['@id' => route('public.questions.hub').'#dataset'])
        ->and($dataset['@id'])->toBe(route('public.questions.hub').'#dataset')
        ->and($dataset['creator'])->toBe(['@id' => 'https://prawkonaraz.pl/#organization'])
        ->and($dataset['hasPart'])->toHaveCount(2)
        ->and($itemList['@id'])->toBe(route('public.questions.hub').'#categories')
        ->and($itemList['numberOfItems'])->toBe(2)
        ->and(data_get($itemList, 'itemListElement.0.item.@id'))->toBe('https://prawkonaraz.pl/entity/category/a');

    expect($graph)->toHaveCount(8);
});

test('public question hub redirects exact prompt search to canonical shared question', function () {
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 2,
    ]);
    $prompt = 'Czy masz obowiązek zatrzymać swój pojazd przed ostatnim wagonem tramwaju?';

    foreach ([$categoryA, $categoryB] as $category) {
        Question::factory()
            ->booleanType()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => '3540',
                'prompt' => $prompt,
                'correct_answer' => 'a',
                'source' => 'gov.pl-mi',
            ]);
    }

    $this->get(route('public.questions.hub', ['q' => $prompt]))
        ->assertRedirect(route('public.questions.show', [
            'externalId' => '3540',
            'slug' => Str::slug($prompt),
        ]));
});

test('legacy public question search route renders without redirect loop', function () {
    $this->get(route('public.questions.search', ['q' => 'test']))
        ->assertOk();
});

test('public question hub keeps legacy question parameter for id lookup', function () {
    $category = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy masz obowiązek zatrzymać swój pojazd przed ostatnim wagonem tramwaju?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '3540',
            'prompt' => $prompt,
            'correct_answer' => 'a',
            'source' => 'gov.pl-mi',
        ]);

    $this->get(route('public.questions.hub', ['question' => '3540']))
        ->assertRedirect(route('public.questions.show', [
            'externalId' => '3540',
            'slug' => Str::slug($prompt),
        ]));
});

test('public question hub renders grouped text results for shared questions', function () {
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 2,
    ]);
    $prompt = 'Czy masz obowiązek zatrzymać swój pojazd przed ostatnim wagonem tramwaju?';

    foreach ([$categoryA, $categoryB] as $category) {
        Question::factory()
            ->booleanType()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => '3540',
                'prompt' => $prompt,
                'correct_answer' => 'a',
                'source' => 'gov.pl-mi',
            ]);
    }

    Question::factory()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '9999',
            'prompt' => 'Czy w tej sytuacji możesz ominąć stojący pojazd?',
        ]);

    $response = $this->get(route('public.questions.hub', ['q' => 'ostatnim wagonem']));
    $plainText = questionDatabasePlainText($response);

    $response
        ->assertOk()
        ->assertSeeText('Wyniki wyszukiwania')
        ->assertSeeText('ostatnim wagonem')
        ->assertSeeText('#3540')
        ->assertSeeText('Kat. A')
        ->assertSeeText('Kat. B')
        ->assertSeeText('Wyświetlono 1-1 z 1 pytań')
        ->assertDontSeeText('#9999');

    expect(substr_count($plainText, $prompt))->toBe(1);
});

test('public question hub keeps display id collisions as separate search results', function () {
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 2,
    ]);
    $standardPrompt = 'Czy standardowe pytanie o numerze 3540 dotyczy zatrzymania pojazdu?';
    $auxiliaryPrompt = 'Czy pomocnicze pytanie o numerze 3540 pokazuje inną sytuację drogową?';

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '3540',
            'prompt' => $standardPrompt,
            'correct_answer' => 'a',
            'source' => 'gov.pl-mi',
        ]);

    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => 'pj360:3540',
            'prompt' => $auxiliaryPrompt,
            'correct_answer' => 'b',
            'source' => 'gov.pl-mi',
        ]);

    $this->get(route('public.questions.hub', ['q' => 'numerze 3540']))
        ->assertOk()
        ->assertSeeText('#3540')
        ->assertSeeText($standardPrompt)
        ->assertSeeText($auxiliaryPrompt)
        ->assertSeeText('Wyświetlono 1-2 z 2 pytań');
});

test('public question category page renders clean paginated question list', function () {
    $category = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
        ]);

    $this->get(route('public.questions.category', $category->slug))
        ->assertOk()
        ->assertSeeText('Kategoria A')
        ->assertSeeText('Pytania egzaminacyjne na motocykl bez ograniczenia mocy.')
        ->assertSee('placeholder="Szukaj pytań..."', false)
        ->assertDontSeeText('Zestawienie pytań')
        ->assertDontSeeText('Filtry')
        ->assertDontSeeText('ID GOV z bazy')
        ->assertDontSeeText('Zakres numerów')
        ->assertDontSeeText('Losowe pytanie')
        ->assertDontSeeText('Siatka')
        ->assertSeeText('#99')
        ->assertSeeText('Wyświetlono 1-1 z 1 pytań');
});

test('public question category renders a single schema graph foundation', function () {
    $category = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
        ]);

    $response = $this->get(route('public.questions.category', $category->slug));
    $organization = questionDatabaseGraphNode($response, 'Organization');
    $website = questionDatabaseGraphNode($response, 'WebSite');
    $breadcrumb = questionDatabaseGraphNode($response, 'BreadcrumbList');
    $collectionPage = questionDatabaseGraphNode($response, 'CollectionPage');
    $dataset = questionDatabaseGraphNode($response, 'Dataset');
    $definedTerm = questionDatabaseGraphNode($response, 'DefinedTerm');
    $itemList = questionDatabaseGraphNode($response, 'ItemList');

    expect($organization)->toBeArray()
        ->and($website)->toBeArray()
        ->and($breadcrumb)->toBeArray()
        ->and($collectionPage)->toBeArray()
        ->and($dataset)->toBeArray()
        ->and($definedTerm)->toBeArray()
        ->and($itemList)->toBeArray()
        ->and(questionDatabaseGraphDuplicateIds($response))->toBe([])
        ->and($organization['name'])->toBe('PrawkoNaRaz')
        ->and($website['name'])->toBe('PrawkoNaRaz')
        ->and($collectionPage['@id'])->toBe(route('public.questions.category', $category->slug).'#webpage')
        ->and($collectionPage['isPartOf'])->toBe(['@id' => 'https://prawkonaraz.pl/#website'])
        ->and($collectionPage['breadcrumb'])->toBe(['@id' => route('public.questions.category', $category->slug).'#breadcrumb'])
        ->and($collectionPage['mainEntity'])->toBe(['@id' => 'https://prawkonaraz.pl/entity/category/a'])
        ->and($collectionPage['hasPart'])->toBe(['@id' => route('public.questions.category', $category->slug).'#questions'])
        ->and($dataset['hasPart'])->toBe([['@id' => 'https://prawkonaraz.pl/entity/category/a']])
        ->and($definedTerm['@id'])->toBe('https://prawkonaraz.pl/entity/category/a')
        ->and($definedTerm['termCode'])->toBe('A')
        ->and(data_get($definedTerm, 'isPartOf.@id'))->toBe(route('public.questions.hub').'#dataset')
        ->and(data_get($definedTerm, 'hasPart.0.@id'))->toBe(route('public.questions.show', [
            'externalId' => '99',
            'slug' => Str::slug('Czy w tej sytuacji masz obowiązek zatrzymać pojazd?'),
        ]).'#question')
        ->and($itemList['@id'])->toBe(route('public.questions.category', $category->slug).'#questions')
        ->and($itemList['numberOfItems'])->toBe(1)
        ->and(data_get($itemList, 'itemListElement.0.name'))->toBe('Pytanie 99');
});

test('public question category filters by official gov id', function () {
    $category = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
        ]);
    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'pj360:100',
            'prompt' => 'Czy w tej sytuacji powinieneś oczekiwać następnego sygnału?',
        ]);

    $this->get(route('public.questions.category', [
        'categorySlug' => $category->slug,
        'gov_id' => '100',
    ]))
        ->assertOk()
        ->assertSee('value="100"', false)
        ->assertSeeText('#100')
        ->assertDontSeeText('#99')
        ->assertSeeText('Wyświetlono 1-1 z 1 pytań');

    $this->get(route('public.questions.category', [
        'categorySlug' => $category->slug,
        'q' => '100',
    ]))
        ->assertOk()
        ->assertSee('value="100"', false)
        ->assertSeeText('#100')
        ->assertDontSeeText('#99')
        ->assertSeeText('Wyświetlono 1-1 z 1 pytań');
});

test('public question category search shares normalized text matching', function () {
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 2,
    ]);
    $prompt = 'Czy masz obowiązek zatrzymać swój pojazd przed ostatnim wagonem tramwaju?';

    foreach ([$categoryA, $categoryB] as $category) {
        Question::factory()
            ->booleanType()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => '3540',
                'prompt' => $prompt,
                'correct_answer' => 'a',
            ]);
    }

    $this->get(route('public.questions.category', [
        'categorySlug' => $categoryB->slug,
        'q' => 'obowiazek zatrzymac swoj pojazd przed ostatnim wagonem tramwaju',
    ]))
        ->assertOk()
        ->assertSeeText('#3540')
        ->assertSeeText($prompt)
        ->assertSeeText('Kat. B')
        ->assertDontSeeText('Kat. A')
        ->assertSeeText('Wyświetlono 1-1 z 1 pytań');
});

test('public question category search ignores inline prompt formatting markers', function () {
    $category = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'FORMAT-YES',
            'prompt' => 'Czy w przedstawionej [green]**sytuacji**[/green] jesteś ostrzegany o [red]kilku[/red] niebezpiecznych zakrętach w lewo?',
        ]);
    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'FORMAT-NO',
            'prompt' => 'Pytanie kontrolne bez tego zestawu słów.',
        ]);

    $this->get(route('public.questions.category', [
        'categorySlug' => $category->slug,
        'q' => 'Czy w przedstawionej sytuacji jesteś ostrzegany o kilku niebezpiecznych zakrętach w lewo?',
    ]))
        ->assertOk()
        ->assertSeeText('#FORMAT-YES')
        ->assertDontSeeText('#FORMAT-NO')
        ->assertSeeText('Wyświetlono 1-1 z 1 pytań');
});

test('public question detail renders exam layout with display gov id', function () {
    $category = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji przy wjeżdżaniu na jezdnię należy zachować szczególną ostrożność?';
    $nextPrompt = 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?';
    $nextQuestionUrl = route('public.questions.category.show', [
        'categorySlug' => $category->slug,
        'externalId' => '3006',
        'slug' => Str::slug($nextPrompt),
    ]);

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'pj360:3005',
            'prompt' => $prompt,
            'explanation' => 'Tak, podczas wjeżdżania na jezdnię trzeba zachować szczególną ostrożność.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 3,
            'source' => 'gov.pl-mi',
            'updated_at' => now()->setDate(2026, 5, 14),
        ]);
    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'pj360:3006',
            'prompt' => $nextPrompt,
            'correct_answer' => 'a',
            'points' => 3,
            'source' => 'gov.pl-mi',
        ]);

    $this->get(route('public.questions.show', [
        'externalId' => 'pj360:3005',
        'slug' => Str::slug($prompt),
    ]))
        ->assertRedirect('https://prawkonaraz.pl/pytanie/3005/'.Str::slug($prompt));

    $this->get(route('public.questions.show', [
        'externalId' => '3005',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('Kategoria A')
        ->assertSeeText($prompt)
        ->assertSeeText('Numer')
        ->assertSeeText('3005')
        ->assertSeeText('Poprawna odpowiedź')
        ->assertSeeText('TAK')
        ->assertSeeText('3 pkt')
        ->assertSeeText('gov.pl')
        ->assertSeeText('Wyjaśnienie')
        ->assertDontSeeText('To poprawna odpowiedź do tego pytania.')
        ->assertSeeText('Zakres kategorii')
        ->assertSeeText('To pytanie występuje wyłącznie w kategorii A.')
        ->assertSeeText('Zakres wynika z przypisania w oficjalnej bazie pytań egzaminacyjnych.')
        ->assertDontSeeText('To pytanie występuje w 1 kategoriach egzaminacyjnych')
        ->assertSeeText('Powiązane pytania')
        ->assertSeeText('Następne pytanie')
        ->assertSee('data-public-question-keyboard-navigation', false)
        ->assertSee('data-previous-url=""', false)
        ->assertSee('data-next-url="'.$nextQuestionUrl.'"', false)
        ->assertSee("event.key === 'ArrowRight'", false)
        ->assertSee("event.key === 'ArrowLeft'", false)
        ->assertSeeText('1 / 2');
});

test('public question detail keeps plural category scope message for multiple categories', function () {
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 2,
    ]);
    $prompt = 'Czy w tej sytuacji należy zachować szczególną ostrożność?';

    foreach ([$categoryA, $categoryB] as $category) {
        Question::factory()
            ->booleanType()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => '3420',
                'prompt' => $prompt,
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'correct_answer' => 'a',
                'source' => 'gov.pl-mi',
            ]);
    }

    $this->get(route('public.questions.show', [
        'externalId' => '3420',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSeeText('To pytanie występuje w 2 kategoriach egzaminacyjnych')
        ->assertSeeText('A')
        ->assertSeeText('B')
        ->assertDontSeeText('To pytanie występuje wyłącznie w kategorii');
});

test('public question category links keep the clicked category context on shared question detail', function () {
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 2,
    ]);
    $categoryC = LicenseCategory::factory()->categoryC()->create([
        'name' => 'Kategoria C',
        'sort_order' => 3,
    ]);
    $prompt = 'Czy w tej sytuacji nadawany sygnał świetlny zezwala Ci na wjazd za sygnalizator?';

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '479',
            'prompt' => 'Czy w tej sytuacji powinieneś oczekiwać następnego sygnału?',
            'correct_answer' => 'a',
        ]);

    foreach ([$categoryA, $categoryB] as $category) {
        Question::factory()
            ->booleanType()
            ->for($category, 'licenseCategory')
            ->create([
                'external_id' => '480',
                'prompt' => $prompt,
                'option_a' => 'Tak',
                'option_b' => 'Nie',
                'correct_answer' => 'b',
                'points' => 3,
                'source' => 'gov.pl-mi',
            ]);
    }

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '481',
            'prompt' => 'Czy możesz kontynuować jazdę po zapaleniu światła zielonego?',
            'correct_answer' => 'a',
        ]);

    $canonicalUrl = route('public.questions.show', [
        'externalId' => '480',
        'slug' => Str::slug($prompt),
    ]);
    $contextUrl = route('public.questions.category.show', [
        'categorySlug' => $categoryB->slug,
        'externalId' => '480',
        'slug' => Str::slug($prompt),
    ]);

    $categoryResponse = $this->get(route('public.questions.category', $categoryB->slug));
    $itemList = questionDatabaseGraphNode($categoryResponse, 'ItemList');
    $questionListItem = collect(data_get($itemList, 'itemListElement', []))
        ->first(fn (mixed $item): bool => is_array($item) && data_get($item, 'name') === 'Pytanie 480');

    $categoryResponse
        ->assertOk()
        ->assertSee('href="'.$contextUrl.'"', false)
        ->assertSeeText('Kat. B');
    expect($questionListItem)->toBeArray()
        ->and(data_get($questionListItem, 'url'))->toBe($contextUrl)
        ->and(data_get($questionListItem, 'item.@id'))->toBe($canonicalUrl.'#question')
        ->and(data_get($questionListItem, 'item.url'))->toBe($canonicalUrl);

    $response = $this->get($contextUrl);
    $breadcrumb = questionDatabaseGraphNode($response, 'BreadcrumbList');
    $questionSchema = questionDatabaseGraphNode($response, 'Question');
    $definedTerm = collect(questionDatabaseJsonLdGraph($response))
        ->first(fn (mixed $node): bool => is_array($node)
            && ($node['@type'] ?? null) === 'DefinedTerm'
            && ($node['termCode'] ?? null) === 'B');

    $response
        ->assertOk()
        ->assertSeeText('Kategoria B')
        ->assertSeeText('To pytanie występuje w 2 kategoriach egzaminacyjnych')
        ->assertSeeText('2 / 3')
        ->assertSee('<link rel="canonical" href="'.$canonicalUrl.'">', false)
        ->assertSee('<meta property="og:url" content="'.$canonicalUrl.'">', false)
        ->assertSee('pytanie 480 kat. B', false);
    expect(questionDatabaseGraphDuplicateIds($response))->toBe([])
        ->and(data_get($breadcrumb, 'itemListElement.2.name'))->toBe('Kategoria B')
        ->and(data_get($questionSchema, '@id'))->toBe($canonicalUrl.'#question')
        ->and(data_get($questionSchema, 'isPartOf.@id'))->toBe('https://prawkonaraz.pl/entity/category/b')
        ->and(collect(data_get($questionSchema, 'about', []))->contains(['@id' => 'https://prawkonaraz.pl/entity/category/b']))->toBeTrue()
        ->and($definedTerm)->toBeArray();

    $this->get(route('public.questions.category.show', [
        'categorySlug' => $categoryB->slug,
        'externalId' => '480',
        'slug' => 'nieaktualny-slug',
    ]))->assertRedirect($contextUrl);

    $this->get(route('public.questions.category.show', [
        'categorySlug' => $categoryC->slug,
        'externalId' => '480',
        'slug' => Str::slug($prompt),
    ]))->assertNotFound();
});

test('public question detail uses public explanation instead of system explanation', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $author = ContentAuthor::factory()
        ->published()
        ->create([
            'name' => 'Katarzyna Wiśniewska',
            'slug' => 'katarzyna-wisniewska',
        ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?';
    $relatedPrompt = 'Czy w tej sytuacji wystarczy tylko zmniejszyć prędkość?';
    $examExplanation = 'Tak, masz obowiązek zatrzymać pojazd przed linią wyznaczoną.';
    $publicBody = 'Publiczne omówienie opisuje bezpieczne opuszczenie tramwaju przez pasażerów i wyjaśnia, dlaczego kierujący powinien zatrzymać pojazd bez próby przejazdu obok przystanku.';
    $dontConfuseWith = 'Nie myl tej sytuacji ze zwykłym przystankiem z wysepką. Tutaj pasażerowie przechodzą przez tor jazdy pojazdów.';
    $examTrap = 'W tym pytaniu nie chodzi tylko o obecność tramwaju. Kluczowe jest to, że tramwaj znajduje się na oznaczonym przystanku bez wysepki dla pasażerów. W takiej sytuacji kierujący musi zatrzymać pojazd, a nie tylko zwolnić.';
    $commonMistakes = [
        [
            'title' => 'Wystarczy tylko zwolnić.',
            'explanation' => 'Samo zmniejszenie prędkości nie zastępuje obowiązku zatrzymania pojazdu.',
        ],
        [
            'title' => 'Przejście dla pieszych jest konieczne.',
            'explanation' => 'Obowiązek wynika z obsługi przystanku bez wysepki.',
        ],
        [
            'title' => 'Zamknięte drzwi pozwalają od razu ruszyć.',
            'explanation' => 'Najpierw trzeba zapewnić pasażerom swobodny dostęp do chodnika.',
        ],
    ];

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => $prompt,
            'explanation' => $examExplanation,
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 3,
        ]);
    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '101',
            'prompt' => $relatedPrompt,
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
            'points' => 3,
        ]);
    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '102',
            'prompt' => 'Czy pasażerowie tramwaju mogą pojawić się na jezdni?',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'points' => 3,
        ]);

    QuestionPublicExplanation::factory()
        ->published()
        ->create([
            'external_id' => '99',
            'title' => 'Omówienie sytuacji',
            'body' => $publicBody,
            'dont_confuse_with' => $dontConfuseWith,
            'exam_trap' => $examTrap,
            'common_mistakes' => $commonMistakes,
            'related_questions' => [
                [
                    'external_id' => '101',
                    'description' => 'pytanie o błąd polegający na samym **zmniejszeniu prędkości**.',
                ],
                [
                    'external_id' => '102',
                    'description' => 'pytanie o obserwację pasażerów przechodzących przez jezdnię.',
                ],
            ],
            'author_id' => $author->getKey(),
            'last_reviewed_at' => now()->setDate(2026, 6, 15),
        ]);

    $response = $this->get(route('public.questions.show', [
        'externalId' => '99',
        'slug' => Str::slug($prompt),
    ]));

    $response
        ->assertOk()
        ->assertSeeText('Wyjaśnienie')
        ->assertSeeText($publicBody)
        ->assertSeeText('Aktualizacja wyjaśnienia: 15.06.2026')
        ->assertSeeText('Katarzyna Wiśniewska')
        ->assertSee('href="'.route('content-authors.show', $author->slug).'"', false)
        ->assertSeeText('Nie pomyl z')
        ->assertSeeText('Nie myl tej sytuacji ze zwykłym przystankiem z wysepką.')
        ->assertSeeText('Haczyk egzaminacyjny')
        ->assertSeeText($examTrap)
        ->assertSeeText('Najczęstsze błędy')
        ->assertSeeText('Wystarczy tylko zwolnić.')
        ->assertSeeText('Samo zmniejszenie prędkości nie zastępuje obowiązku zatrzymania pojazdu.')
        ->assertSeeText('Przejście dla pieszych jest konieczne.')
        ->assertSeeText('Zamknięte drzwi pozwalają od razu ruszyć.')
        ->assertSeeText('Powiązane pytania')
        ->assertSeeText('Sprawdź podobne sytuacje i utrwal wiedzę przed egzaminem.')
        ->assertSeeText($relatedPrompt)
        ->assertSeeText('pytanie o błąd polegający na samym zmniejszeniu prędkości.')
        ->assertSee('href="'.route('public.questions.category.show', [
            'categorySlug' => $category->slug,
            'externalId' => '101',
            'slug' => Str::slug($relatedPrompt),
        ]).'"', false)
        ->assertDontSeeText($examExplanation)
        ->assertDontSeeText('Omówienie sytuacji')
        ->assertDontSeeText('To pytanie nie ma jeszcze osobnego rozwinięcia')
        ->assertDontSee('data-public-explanation-empty', false);

    $html = $response->getContent();

    expect(strpos($html, 'Wyjaśnienie'))->toBeLessThan(strpos($html, 'Haczyk egzaminacyjny'));
    expect(strpos($html, 'Wyjaśnienie'))->toBeLessThan(strpos($html, 'Nie pomyl z'));
    expect(strpos($html, 'Nie pomyl z'))->toBeLessThan(strpos($html, 'Haczyk egzaminacyjny'));
    expect(strpos($html, 'Haczyk egzaminacyjny'))->toBeLessThan(strpos($html, 'Najczęstsze błędy'));
    expect(strpos($html, 'Najczęstsze błędy'))->toBeLessThan(strpos($html, 'Powiązane pytania'));

    $webPage = questionDatabaseGraphNode($response, 'WebPage');
    $questionSchema = questionDatabaseGraphNode($response, 'Question');

    expect($webPage)->toBeArray();
    expect($questionSchema)->toBeArray();
    expect($webPage['mainEntity'])->toBe([
        '@id' => route('public.questions.show', [
            'externalId' => '99',
            'slug' => Str::slug($prompt),
        ]).'#question',
    ]);
    expect(data_get($questionSchema, 'acceptedAnswer.text'))
        ->toContain('Publiczne omówienie')
        ->not->toContain($examExplanation);
    expect(array_key_exists('suggestedAnswer', $questionSchema))->toBeFalse();
    expect(array_key_exists('answerCount', $questionSchema))->toBeFalse();
});

test('public question sign reference service decorates only first code occurrence per fragment', function () {
    $author = ContentAuthor::factory()->published()->create();
    $signCategory = TrafficSignCategory::factory()->published()->create();

    TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($signCategory, 'category')
        ->create([
            'code' => 'A-10',
            'slug' => 'a-10-przejazd-kolejowy-bez-zapor',
            'name' => 'Przejazd kolejowy bez zapór',
            'intro_definition' => 'Ostrzega o przejeździe kolejowym bez zapór.',
            'image_path' => 'traffic-signs/signs/warnings/a-10-przejazd-kolejowy-bez-zapor.webp',
        ]);
    TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($signCategory, 'category')
        ->create([
            'code' => 'A-9',
            'slug' => 'a-9-przejazd-kolejowy-z-zaporami',
            'name' => 'Przejazd kolejowy z zaporami',
            'intro_definition' => 'Ostrzega o przejeździe kolejowym z zaporami.',
            'image_path' => 'traffic-signs/signs/warnings/a-9-przejazd-kolejowy-z-zaporami.webp',
        ]);

    $html = app(PublicQuestionSignReferenceService::class)
        ->decorateHtml('<p>Widoczny jest znak A-10. A-10 nie oznacza tego samego co A-9.</p>');

    expect(substr_count($html, 'data-sign-code="A-10"'))->toBe(1)
        ->and(substr_count($html, 'data-sign-code="A-9"'))->toBe(1)
        ->and($html)->toContain('A-10 nie oznacza');
});

test('public question detail enriches sign codes with sign thumbnails and modal payload', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $author = ContentAuthor::factory()->published()->create();
    $signCategory = TrafficSignCategory::factory()->published()->create();
    $a10 = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($signCategory, 'category')
        ->create([
            'code' => 'A-10',
            'slug' => 'a-10-przejazd-kolejowy-bez-zapor',
            'name' => 'Przejazd kolejowy bez zapór',
            'intro_definition' => 'Ostrzega o przejeździe kolejowym niewyposażonym w zapory ani półzapory.',
            'image_path' => 'traffic-signs/signs/warnings/a-10-przejazd-kolejowy-bez-zapor.webp',
            'image_alt' => 'Znak A-10 Przejazd kolejowy bez zapór',
        ]);
    $a9 = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($signCategory, 'category')
        ->create([
            'code' => 'A-9',
            'slug' => 'a-9-przejazd-kolejowy-z-zaporami',
            'name' => 'Przejazd kolejowy z zaporami',
            'intro_definition' => 'Ostrzega o przejeździe kolejowym wyposażonym w zapory lub półzapory.',
            'image_path' => 'traffic-signs/signs/warnings/a-9-przejazd-kolejowy-z-zaporami.webp',
            'image_alt' => 'Znak A-9 Przejazd kolejowy z zaporami',
        ]);
    $prompt = 'Czy widoczny znak ostrzega o przejeździe kolejowym bez zapór?';
    $relatedPrompt = 'Czy widoczny znak ostrzega o przejeździe kolejowym z zaporami?';
    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '1533',
            'prompt' => $prompt,
            'correct_answer' => 'a',
        ]);
    $relatedQuestion = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '1534',
            'prompt' => $relatedPrompt,
            'correct_answer' => 'a',
        ]);

    QuestionExplanationAsset::factory()
        ->for($question)
        ->create([
            'traffic_sign_id' => $a10->getKey(),
            'title' => 'Znak A-10',
        ]);
    QuestionExplanationAsset::factory()
        ->for($relatedQuestion)
        ->create([
            'traffic_sign_id' => $a9->getKey(),
            'title' => 'Znak A-9',
        ]);

    QuestionPublicExplanation::factory()
        ->published()
        ->create([
            'external_id' => '1533',
            'body' => 'Widoczny jest znak A-10. Drugie A-10 w tym samym akapicie zostaje zwykłym tekstem, a brakujący Z-999 nie powinien tworzyć miniatury.',
            'dont_confuse_with' => 'Nie myl A-10 z A-9.',
            'exam_trap' => 'Haczyk dotyczy odróżnienia A-10 od A-9.',
            'common_mistakes' => [
                [
                    'title' => 'Mylenie A-10 z A-9.',
                    'explanation' => 'Kursanci często zapamiętują sam kod A-10 zamiast wyglądu znaku.',
                ],
            ],
            'related_questions' => [
                [
                    'external_id' => '1534',
                    'description' => 'Porównaj A-9 z A-10.',
                ],
            ],
        ]);

    $response = $this->get(route('public.questions.show', [
        'externalId' => '1533',
        'slug' => Str::slug($prompt),
    ]));

    $html = $response->getContent();

    $response
        ->assertOk()
        ->assertSee('data-sign-preview-modal', false)
        ->assertSee('data-sign-code="A-10"', false)
        ->assertSee('data-sign-code="A-9"', false)
        ->assertSee('data-sign-title="A-10 Przejazd kolejowy bez zapór"', false)
        ->assertSee('data-sign-title="A-9 Przejazd kolejowy z zaporami"', false)
        ->assertSee('traffic-signs/sign-cutouts/a-10-przejazd-kolejowy-bez-zapor.png', false)
        ->assertSee('traffic-signs/sign-cutouts/a-9-przejazd-kolejowy-z-zaporami.png', false)
        ->assertSee('data-sign-url="'.route('traffic-signs.show', $a10->slug, absolute: false).'"', false)
        ->assertSeeText('Z-999')
        ->assertDontSee('data-sign-code="Z-999"', false)
        ->assertSeeText($relatedPrompt)
        ->assertSeeText('Porównaj');

    expect(substr_count($html, 'data-sign-code="A-10"'))->toBeGreaterThanOrEqual(4)
        ->and(substr_count($html, 'data-sign-code="A-9"'))->toBeGreaterThanOrEqual(3)
        ->and($html)->toContain('Drugie A-10 w tym samym akapicie');
});

test('admin can see inline public explanation editor on public question detail', function () {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?';
    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => $prompt,
            'explanation' => 'Tak, systemowe wyjaśnienie zostaje tylko dla nauki.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
        ]);

    QuestionPublicExplanation::factory()
        ->published()
        ->create([
            'external_id' => '99',
            'body' => 'Publiczne wyjaśnienie dostępne do ręcznej edycji.',
        ]);

    $this->actingAs($admin)
        ->get(route('public.questions.show', [
            'externalId' => '99',
            'slug' => Str::slug($prompt),
        ]))
        ->assertOk()
        ->assertSee('data-public-explanation-editor', false)
        ->assertSee('data-public-explanation-edit', false)
        ->assertSee('data-public-explanation-exam-trap', false)
        ->assertSee('data-public-explanation-mistakes-editor', false)
        ->assertSee('data-public-explanation-mistake-add', false)
        ->assertSee('data-public-explanation-mistake-template', false)
        ->assertSee('data-question-graphic-edit', false)
        ->assertSee(
            QuestionResource::getUrl('edit', ['record' => $question], panel: 'admin'),
            false,
        )
        ->assertSeeText('Edytuj grafikę')
        ->assertSee('Edytuj')
        ->assertSee('Zapisz')
        ->assertSee(route('api.v1.admin.questions.public-explanation.update', $question), false)
        ->assertSee('data-legal-reference-editor', false)
        ->assertSee('data-legal-reference-edit', false)
        ->assertSee(route('api.v1.admin.questions.legal-reference.update', $question), false)
        ->assertSee('Opis prawny')
        ->assertDontSee('question_public_explanations', false)
        ->assertDontSee('questions.explanation', false);
});

test('guest and non admin cannot see inline public explanation editor', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy możesz kontynuować jazdę?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '777',
            'prompt' => $prompt,
            'explanation' => 'Nie, w tej sytuacji nie możesz kontynuować jazdy.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ]);

    $route = route('public.questions.show', [
        'externalId' => '777',
        'slug' => Str::slug($prompt),
    ]);

    $this->get($route)
        ->assertOk()
        ->assertDontSee('data-public-explanation-editor', false)
        ->assertDontSee('data-public-explanation-edit', false)
        ->assertDontSee('data-question-graphic-edit', false)
        ->assertDontSee('data-legal-reference-editor', false)
        ->assertDontSee('data-legal-reference-edit', false)
        ->assertDontSee('Publiczne wyjaśnienie SEO');

    $this->actingAs($user)
        ->get($route)
        ->assertOk()
        ->assertDontSee('data-public-explanation-editor', false)
        ->assertDontSee('data-public-explanation-edit', false)
        ->assertDontSee('data-question-graphic-edit', false)
        ->assertDontSee('data-legal-reference-editor', false)
        ->assertDontSee('data-legal-reference-edit', false)
        ->assertDontSee('Publiczne wyjaśnienie SEO');
});

test('public question detail renders verified legal basis without linked article', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek umożliwić pasażerom opuszczenie tramwaju?';
    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '32100',
            'prompt' => $prompt,
            'explanation' => 'Systemowe wyjaśnienie.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'a',
            'updated_at' => now()->setDate(2026, 5, 14),
        ]);
    $legalAct = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym-render-test',
        'title' => 'Prawo o ruchu drogowym',
        'short_title' => 'Prawo o ruchu drogowym',
        'source_url' => 'https://example.test/pord',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $legalUnit = LegalUnit::query()->create([
        'legal_act_id' => $legalAct->getKey(),
        'type' => 'article',
        'label' => 'art. 26 ust. 6',
        'slug' => 'art-26-ust-6-render-test',
        'title' => 'Obowiązki kierującego przy przystankach tramwajowych',
        'official_excerpt' => 'Kierujący pojazdem jest obowiązany zachować szczególną ostrożność przy przejeżdżaniu obok oznaczonego przystanku tramwajowego.',
        'source_url' => 'https://example.test/pord/art-26',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);
    ContentAuthor::factory()->published()->create([
        'name' => 'Jakub Wiśniewski',
        'slug' => ContentAuthor::DEFAULT_LEGAL_REFERENCE_VERIFIER_SLUG,
    ]);
    QuestionLegalReference::query()->create([
        'question_id' => $question->getKey(),
        'legal_unit_id' => $legalUnit->getKey(),
        'legal_topic_id' => null,
        'legal_content_page_id' => null,
        'public_note' => null,
        'status' => QuestionLegalReference::STATUS_VERIFIED,
        'verified_at' => now()->setDate(2026, 6, 15),
    ]);
    QuestionPublicExplanation::factory()
        ->published()
        ->create([
            'external_id' => '32100',
            'body' => 'Publiczne omówienie sytuacji z pasażerami tramwaju.',
            'last_reviewed_at' => now()->setDate(2026, 6, 22),
            'common_mistakes' => [
                [
                    'title' => 'Wystarczy tylko zwolnić.',
                    'explanation' => 'W tej sytuacji wymagane jest zatrzymanie pojazdu.',
                ],
            ],
        ]);

    $response = $this->get(route('public.questions.show', [
        'externalId' => '32100',
        'slug' => Str::slug($prompt),
    ]));

    $response->assertOk()
        ->assertSeeText('Uzasadnienie prawne')
        ->assertSeeText('Aktualizacja pytania: 14.05.2026')
        ->assertSeeText('Aktualizacja wyjaśnienia: 22.06.2026')
        ->assertSeeText('Weryfikacja podstawy prawnej: 15.06.2026 · Jakub Wiśniewski')
        ->assertSee('href="'.route('content-authors.show', 'jakub-wisniewski').'"', false)
        ->assertSeeText('Najczęstsze błędy')
        ->assertSeeText('Prawo o ruchu drogowym')
        ->assertSeeText('art. 26 ust. 6')
        ->assertSeeText('Treść przepisu')
        ->assertSeeText('Kierujący pojazdem jest obowiązany zachować szczególną ostrożność przy przejeżdżaniu obok oznaczonego przystanku tramwajowego.')
        ->assertDontSeeText('Zobacz opracowanie przepisu');

    $html = $response->getContent();

    expect(strpos($html, 'Wyjaśnienie'))->toBeLessThan(strpos($html, 'Najczęstsze błędy'));
    expect(strpos($html, 'Najczęstsze błędy'))->toBeLessThan(strpos($html, 'Uzasadnienie prawne'));

    $questionSchema = questionDatabaseGraphNode($response, 'Question');
    $legislation = questionDatabaseGraphNode($response, 'Legislation');

    expect($questionSchema)->toBeArray();
    expect($legislation)->toBeArray();
    expect($legislation['@id'])->toBe('https://prawkonaraz.pl/entity/law/art-26-ust-6-render-test');
    expect($legislation['name'])->toBe('Prawo o ruchu drogowym art. 26 ust. 6');
    expect($legislation['text'])->toContain('Kierujący pojazdem jest obowiązany');
    expect($legislation['url'])->toBe('https://example.test/pord/art-26');
    expect(collect(data_get($questionSchema, 'about'))->contains(['@id' => $legislation['@id']]))->toBeTrue();
});

test('public question detail does not expose draft legal basis in schema', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy możesz kontynuować jazdę po torowisku?';
    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '32101',
            'prompt' => $prompt,
            'explanation' => 'Systemowe wyjaśnienie.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ]);
    $legalAct = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym-draft-test',
        'title' => 'Prawo o ruchu drogowym',
        'short_title' => 'Prawo o ruchu drogowym',
        'source_url' => 'https://example.test/pord',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $legalUnit = LegalUnit::query()->create([
        'legal_act_id' => $legalAct->getKey(),
        'type' => 'article',
        'label' => 'art. 16 ust. 1',
        'slug' => 'art-16-ust-1-draft-test',
        'title' => 'Ruch prawostronny',
        'official_excerpt' => 'Ten szkic podstawy prawnej nie powinien być widoczny publicznie.',
        'source_url' => 'https://example.test/pord/art-16',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);
    QuestionLegalReference::query()->create([
        'question_id' => $question->getKey(),
        'legal_unit_id' => $legalUnit->getKey(),
        'public_note' => null,
        'status' => QuestionLegalReference::STATUS_DRAFT,
        'verified_at' => null,
    ]);

    $response = $this->get(route('public.questions.show', [
        'externalId' => '32101',
        'slug' => Str::slug($prompt),
    ]));

    $response->assertOk()
        ->assertDontSeeText('Uzasadnienie prawne')
        ->assertDontSeeText('Ten szkic podstawy prawnej nie powinien być widoczny publicznie.');

    expect(questionDatabaseGraphNode($response, 'Legislation'))->toBeNull();
});

test('public question detail hides draft public seo explanation', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy możesz kontynuować jazdę?';

    Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '777',
            'prompt' => $prompt,
            'explanation' => 'Nie, w tej sytuacji nie możesz kontynuować jazdy.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ]);

    QuestionPublicExplanation::factory()
        ->create([
            'external_id' => '777',
            'title' => 'Omówienie sytuacji',
            'body' => 'Ten szkic nie powinien być widoczny publicznie.',
            'dont_confuse_with' => 'Ta sekcja szkicu również nie powinna być widoczna publicznie.',
            'common_mistakes' => [
                [
                    'title' => 'Ten błąd również nie powinien być widoczny.',
                    'explanation' => 'Treść jest częścią szkicu.',
                ],
            ],
        ]);

    $response = $this->get(route('public.questions.show', [
        'externalId' => '777',
        'slug' => Str::slug($prompt),
    ]));

    $response
        ->assertOk()
        ->assertSeeText('Nie, w tej sytuacji nie możesz kontynuować jazdy.')
        ->assertDontSeeText('Ten szkic nie powinien być widoczny publicznie.')
        ->assertDontSeeText('Ta sekcja szkicu również nie powinna być widoczna publicznie.')
        ->assertDontSeeText('Nie pomyl z')
        ->assertDontSeeText('Najczęstsze błędy')
        ->assertDontSeeText('Ten błąd również nie powinien być widoczny.');

    $questionSchema = questionDatabaseGraphNode($response, 'Question');

    expect($questionSchema)->toBeArray();
    expect(data_get($questionSchema, 'acceptedAnswer.text'))
        ->toContain('Nie, w tej sytuacji nie możesz kontynuować jazdy.')
        ->not->toContain('Ten szkic nie powinien być widoczny publicznie.');
    expect(questionDatabaseGraphNode($response, 'ImageObject'))->toBeNull();
    expect(questionDatabaseGraphNode($response, 'VideoObject'))->toBeNull();
});

test('public question detail uses neutral auxiliary id when display id collides', function () {
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 2,
    ]);
    $standardPrompt = 'Czy standardowe pytanie o numerze 3540 dotyczy zatrzymania pojazdu?';
    $auxiliaryPrompt = 'Czy pomocnicze pytanie o numerze 3540 pokazuje inną sytuację drogową?';

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '3540',
            'prompt' => $standardPrompt,
            'correct_answer' => 'a',
            'points' => 3,
            'source' => 'gov.pl-mi',
        ]);

    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => 'pj360:3540',
            'prompt' => $auxiliaryPrompt,
            'correct_answer' => 'b',
            'points' => 2,
            'source' => 'gov.pl-mi',
        ]);

    $this->get(route('public.questions.show', [
        'externalId' => 'pj360:3540',
        'slug' => Str::slug($auxiliaryPrompt),
    ]))
        ->assertRedirect('https://prawkonaraz.pl/pytanie/pytanie-pomocnicze-3540/'.Str::slug($auxiliaryPrompt));

    $this->get(route('public.questions.show', [
        'externalId' => 'pytanie-pomocnicze-3540',
        'slug' => Str::slug($auxiliaryPrompt),
    ]))
        ->assertOk()
        ->assertSeeText($auxiliaryPrompt)
        ->assertDontSeeText($standardPrompt);

    $this->get(route('public.questions.show', [
        'externalId' => '3540',
        'slug' => Str::slug($standardPrompt),
    ]))
        ->assertOk()
        ->assertSeeText($standardPrompt)
        ->assertDontSeeText($auxiliaryPrompt);
});

test('public question detail exposes descriptive image alt dimensions and social image metadata', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd przed znakiem STOP?';
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '7001',
            'prompt' => $prompt,
            'explanation' => 'Tak, znak STOP wymaga zatrzymania pojazdu.',
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/7001/full.webp',
            'poster_path' => null,
            'mime_type' => 'image/webp',
            'width' => 1280,
            'height' => 720,
            'variant' => 'full',
            'sort_order' => 0,
        ]);

    $expectedAlt = 'Ilustracja do pytania 7001: '.$prompt;

    $response = $this->get(route('public.questions.show', [
        'externalId' => '7001',
        'slug' => Str::slug($prompt),
    ]));

    $response->assertOk()
        ->assertSee('src="https://prawkonaraz.pl/storage/questions/7001/full.webp"', false)
        ->assertSee('alt="'.$expectedAlt.'"', false)
        ->assertSee('width="1280"', false)
        ->assertSee('height="720"', false)
        ->assertSee('fetchpriority="high"', false)
        ->assertSee('meta property="og:image" content="https://prawkonaraz.pl/storage/questions/7001/full.webp"', false)
        ->assertSee('meta property="og:image:alt" content="'.$expectedAlt.'"', false)
        ->assertSee('meta property="og:image:width" content="1280"', false)
        ->assertSee('meta property="og:image:height" content="720"', false)
        ->assertSee('meta name="twitter:image:alt" content="'.$expectedAlt.'"', false)
        ->assertDontSee('"@type":"VideoObject"', false);

    $webPage = questionDatabaseGraphNode($response, 'WebPage');
    $questionSchema = questionDatabaseGraphNode($response, 'Question');
    $imageObject = questionDatabaseGraphNode($response, 'ImageObject');
    $learningResource = questionDatabaseGraphNode($response, 'LearningResource');

    expect($webPage)->toBeArray();
    expect($questionSchema)->toBeArray();
    expect($imageObject)->toBeArray();
    expect($learningResource)->toBeArray();
    expect($imageObject['@id'])->toBe(route('public.questions.show', [
        'externalId' => '7001',
        'slug' => Str::slug($prompt),
    ]).'#image');
    expect($imageObject['contentUrl'])->toBe('https://prawkonaraz.pl/storage/questions/7001/full.webp');
    expect($imageObject['caption'])->toBe($expectedAlt);
    expect($imageObject['encodingFormat'])->toBe('image/webp');
    expect($imageObject['width'])->toBe(1280);
    expect($imageObject['height'])->toBe(720);
    expect($webPage['primaryImageOfPage'])->toBe(['@id' => $imageObject['@id']]);
    expect($questionSchema['image'])->toBe(['@id' => $imageObject['@id']]);
    expect($learningResource['@type'])->toBe('LearningResource');
    expect(questionDatabaseGraphNode($response, 'VideoObject'))->toBeNull();
});

test('public question detail renders ready question audio and audio schema', function () {
    Storage::fake('media_local');
    config()->set('media.public_disk', 'media_local');
    config()->set('media.public_base_url', 'https://prawkonaraz.pl/storage-bulk');

    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?';
    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => $prompt,
            'explanation' => 'Tak, musisz zatrzymać pojazd.',
            'published_at' => now()->setDate(2026, 6, 24)->setTime(11, 30),
        ]);
    $item = questionDatabaseAttachGeneratedAudio($question);
    $audioUrl = 'https://prawkonaraz.pl/storage-bulk/'.$item['target_storage_path'];

    $response = $this->get(route('public.questions.show', [
        'externalId' => '99',
        'slug' => Str::slug($prompt),
    ]));

    $response
        ->assertOk()
        ->assertSee('data-question-audio', false)
        ->assertSeeText('Audio pytania')
        ->assertSeeText('Odsłuchaj treść pytania 99')
        ->assertSee('data-audio-url="'.$audioUrl.'"', false)
        ->assertSee('data-audio-duration="3.200"', false)
        ->assertSee('data-question-audio-time>0:00 / 0:03</span>', false)
        ->assertDontSee('data-question-audio-time>0:00</span>', false)
        ->assertSee('data-question-audio-player', false)
        ->assertSee('data-question-audio-toggle', false)
        ->assertSee('data-question-audio-seek', false)
        ->assertSee('data-question-audio-progress', false)
        ->assertSee('data-question-audio-knob', false)
        ->assertSee('<audio', false)
        ->assertSee('controlslist="nodownload noremoteplayback"', false)
        ->assertSee('disableremoteplayback', false)
        ->assertSee('oncontextmenu="return false"', false)
        ->assertSee('preload="metadata"', false)
        ->assertDontSee('<source src="'.$audioUrl.'"', false)
        ->assertSee('"@type":"AudioObject"', false)
        ->assertSee('"contentUrl":"'.$audioUrl.'"', false)
        ->assertSee('"transcript":"'.$prompt.'"', false)
        ->assertDontSee('"@type":"VideoObject"', false);

    $webPage = questionDatabaseGraphNode($response, 'WebPage');
    $learningResource = questionDatabaseGraphNode($response, 'LearningResource');
    $audioObject = questionDatabaseGraphNode($response, 'AudioObject');
    $canonicalUrl = route('public.questions.show', [
        'externalId' => '99',
        'slug' => Str::slug($prompt),
    ]);

    expect($webPage)->toBeArray();
    expect($learningResource)->toBeArray();
    expect($audioObject)->toBeArray();
    expect($learningResource['isAccessibleForFree'])->toBeTrue();
    expect($learningResource['hasPart'])->toBe([
        ['@id' => $canonicalUrl.'#question'],
        ['@id' => $canonicalUrl.'#audio-question'],
    ]);
    expect($audioObject['@id'])->toBe($canonicalUrl.'#audio-question');
    expect($audioObject['contentUrl'])->toBe($audioUrl);
    expect($audioObject['duration'])->toBe('PT3S');
    expect($audioObject['encodingFormat'])->toBe('audio/mpeg');
    expect($audioObject['mainEntityOfPage'])->toBe(['@id' => $canonicalUrl.'#webpage']);
    expect($audioObject['potentialAction'])->toBe([
        '@type' => 'ListenAction',
        'target' => $canonicalUrl,
    ]);
    expect($webPage['hasPart'])->toBe(['@id' => $canonicalUrl.'#audio-question']);
    expect(questionDatabaseGraphDuplicateIds($response))->toBe([]);
});

test('public question detail renders unknown question audio duration with a consistent time placeholder', function () {
    Storage::fake('media_local');
    config()->set('media.public_disk', 'media_local');
    config()->set('media.public_base_url', 'https://prawkonaraz.pl/storage-bulk');

    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?';
    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '99',
            'prompt' => $prompt,
            'published_at' => now()->setDate(2026, 6, 24)->setTime(11, 30),
        ]);

    questionDatabaseAttachGeneratedAudio($question, null);

    $this->get(route('public.questions.show', [
        'externalId' => '99',
        'slug' => Str::slug($prompt),
    ]))
        ->assertOk()
        ->assertSee('data-question-audio-time>0:00 / --:--</span>', false)
        ->assertDontSee('data-question-audio-time>0:00</span>', false)
        ->assertDontSee('data-audio-duration=', false);
});

test('public question detail exposes video poster as social image and labels the public video only', function () {
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy film pokazuje sytuację wymagającą ustąpienia pierwszeństwa pieszemu?';
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '7002',
            'prompt' => $prompt,
            'explanation' => 'Należy ustąpić pierwszeństwa pieszemu znajdującemu się przy przejściu.',
            'published_at' => now()->setDate(2026, 5, 2)->setTime(10, 15),
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/7002/clip.mp4',
            'poster_path' => 'questions/7002/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 22,
            'width' => 1920,
            'height' => 1080,
            'variant' => 'full',
            'sort_order' => 0,
        ]);

    $expectedAlt = 'Kadr z filmu do pytania 7002: '.$prompt;

    $response = $this->get(route('public.questions.show', [
        'externalId' => '7002',
        'slug' => Str::slug($prompt),
    ]));

    $response
        ->assertOk()
        ->assertSee('<video', false)
        ->assertSee('poster="https://prawkonaraz.pl/storage/questions/7002/poster.webp"', false)
        ->assertSee('aria-label="'.$expectedAlt.'"', false)
        ->assertSee('width="1920"', false)
        ->assertSee('height="1080"', false)
        ->assertSee('<source src="https://prawkonaraz.pl/storage/questions/7002/clip.mp4" type="video/mp4"', false)
        ->assertSee('meta property="og:image" content="https://prawkonaraz.pl/storage/questions/7002/poster.webp"', false)
        ->assertSee('meta property="og:image:alt" content="'.$expectedAlt.'"', false)
        ->assertDontSee('meta property="og:image" content="https://prawkonaraz.pl/storage/questions/7002/clip.mp4"', false)
        ->assertSee('"@type":"VideoObject"', false)
        ->assertSee('"@graph"', false)
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('"name":"Film do pytania 7002:', false)
        ->assertSee('"thumbnailUrl":["https://prawkonaraz.pl/storage/questions/7002/poster.webp"]', false)
        ->assertSee('"contentUrl":"https://prawkonaraz.pl/storage/questions/7002/clip.mp4"', false)
        ->assertSee('"duration":"PT22S"', false)
        ->assertSee('"encodingFormat":"video/mp4"', false)
        ->assertSee('"width":1920', false)
        ->assertSee('"height":1080', false)
        ->assertSee('"uploadDate":"2026-05-02T10:15:00', false)
        ->assertDontSee('"embedUrl"', false)
        ->assertDontSee('"suggestedAnswer"', false)
        ->assertDontSee('"answerCount"', false);

    $organization = questionDatabaseGraphNode($response, 'Organization');
    $website = questionDatabaseGraphNode($response, 'WebSite');
    $breadcrumb = questionDatabaseGraphNode($response, 'BreadcrumbList');
    $webPage = questionDatabaseGraphNode($response, 'WebPage');
    $dataset = questionDatabaseGraphNode($response, 'Dataset');
    $questionSchema = questionDatabaseGraphNode($response, 'Question');
    $learningResource = questionDatabaseGraphNode($response, 'LearningResource');
    $imageObject = questionDatabaseGraphNode($response, 'ImageObject');
    $videoObject = questionDatabaseGraphNode($response, 'VideoObject');
    $canonicalUrl = route('public.questions.show', [
        'externalId' => '7002',
        'slug' => Str::slug($prompt),
    ]);
    $webPageId = $canonicalUrl.'#webpage';
    $questionId = $canonicalUrl.'#question';
    $videoObjectId = $canonicalUrl.'#video-7002';

    expect($organization)->toBeArray();
    expect($website)->toBeArray();
    expect($breadcrumb)->toBeArray();
    expect($webPage)->toBeArray();
    expect($dataset)->toBeArray();
    expect($questionSchema)->toBeArray();
    expect($learningResource)->toBeArray();
    expect($imageObject)->toBeArray();
    expect($videoObject)->toBeArray();
    expect(questionDatabaseGraphDuplicateIds($response))->toBe([]);
    expect($organization['@id'])->toBe('https://prawkonaraz.pl/#organization');
    expect($organization['name'])->toBe('PrawkoNaRaz');
    expect(array_key_exists('legalName', $organization))->toBeFalse();
    expect($organization['email'])->toBe('kontakt@prawkonaraz.pl');
    expect(data_get($organization, 'logo.url'))->toBe('https://prawkonaraz.pl/favicon.png');
    expect(data_get($organization, 'logo.contentUrl'))->toBe('https://prawkonaraz.pl/favicon.png');
    expect(data_get($organization, 'logo.width'))->toBe(256);
    expect(data_get($organization, 'logo.height'))->toBe(256);
    expect($website['@id'])->toBe('https://prawkonaraz.pl/#website');
    expect($website['name'])->toBe('PrawkoNaRaz');
    expect($website['alternateName'])->toBe('prawkonaraz.pl');
    expect(data_get($website, 'potentialAction.target'))->toBe('https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy?q={search_term_string}');
    expect($webPage['@id'])->toBe($webPageId);
    expect($webPage['mainEntity'])->toBe(['@id' => $questionId]);
    expect($webPage['hasPart'])->toBe(['@id' => $videoObjectId]);
    expect($webPage['primaryImageOfPage'])->toBe(['@id' => $canonicalUrl.'#image']);
    expect($dataset['@id'])->toBe(route('public.questions.hub').'#dataset');
    expect($questionSchema['@id'])->toBe($questionId);
    expect($questionSchema['mainEntityOfPage'])->toBe(['@id' => $webPageId]);
    expect($questionSchema['image'])->toBe(['@id' => $canonicalUrl.'#image']);
    expect(array_key_exists('suggestedAnswer', $questionSchema))->toBeFalse();
    expect(array_key_exists('answerCount', $questionSchema))->toBeFalse();
    expect($learningResource['@id'])->toBe($canonicalUrl.'#learning-resource');
    expect($learningResource['about'])->toBe(['@id' => $questionId]);
    expect($learningResource['isPartOf'])->toBe(['@id' => route('public.questions.hub').'#dataset']);
    expect($imageObject['@id'])->toBe($canonicalUrl.'#image');
    expect($imageObject['contentUrl'])->toBe('https://prawkonaraz.pl/storage/questions/7002/poster.webp');
    expect(array_key_exists('encodingFormat', $imageObject))->toBeFalse();
    expect($videoObject['@id'])->toBe($videoObjectId);
    expect(array_key_exists('embedUrl', $videoObject))->toBeFalse();
    expect($videoObject['mainEntityOfPage'])->toBe(['@id' => $webPageId]);
    expect($videoObject['potentialAction'])->toBe([
        '@type' => 'WatchAction',
        'target' => $canonicalUrl,
    ]);
    expect($videoObject['description'])
        ->toContain('Temat sceny: ocena, czy film pokazuje sytuację wymagającą ustąpienia pierwszeństwa pieszemu.')
        ->not->toContain('Należy ustąpić pierwszeństwa pieszemu')
        ->not->toStartWith('Tak.')
        ->not->toStartWith('Nie.');
});

test('public question detail keeps video and audio as webpage parts together', function () {
    Storage::fake('media_local');

    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $prompt = 'Czy film pokazuje obowiązek zatrzymania pojazdu?';
    $question = Question::factory()
        ->booleanType()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => '7102',
            'prompt' => $prompt,
            'explanation' => 'Tak, sytuacja wymaga zatrzymania pojazdu.',
            'published_at' => now()->setDate(2026, 6, 24)->setTime(12, 15),
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/7102/clip.mp4',
            'poster_path' => 'questions/7102/poster.webp',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 12,
            'width' => 1280,
            'height' => 720,
            'variant' => 'full',
            'sort_order' => 0,
        ]);

    questionDatabaseAttachGeneratedAudio($question);

    $canonicalUrl = route('public.questions.show', [
        'externalId' => '7102',
        'slug' => Str::slug($prompt),
    ]);
    $response = $this->get($canonicalUrl);

    $response
        ->assertOk()
        ->assertSee('"@type":"VideoObject"', false)
        ->assertSee('"@type":"AudioObject"', false);

    $webPage = questionDatabaseGraphNode($response, 'WebPage');
    $learningResource = questionDatabaseGraphNode($response, 'LearningResource');
    $videoObject = questionDatabaseGraphNode($response, 'VideoObject');
    $audioObject = questionDatabaseGraphNode($response, 'AudioObject');

    expect($webPage)->toBeArray();
    expect($learningResource)->toBeArray();
    expect($videoObject)->toBeArray();
    expect($audioObject)->toBeArray();
    expect($webPage['hasPart'])->toBe([
        ['@id' => $canonicalUrl.'#video-7102'],
        ['@id' => $canonicalUrl.'#audio-question'],
    ]);
    expect($learningResource['isAccessibleForFree'])->toBeTrue();
    expect($learningResource['hasPart'])->toBe([
        ['@id' => $canonicalUrl.'#question'],
        ['@id' => $canonicalUrl.'#video-7102'],
        ['@id' => $canonicalUrl.'#audio-question'],
    ]);
    expect($videoObject['@id'])->toBe($canonicalUrl.'#video-7102');
    expect($audioObject['@id'])->toBe($canonicalUrl.'#audio-question');
    expect(questionDatabaseGraphDuplicateIds($response))->toBe([]);
});
