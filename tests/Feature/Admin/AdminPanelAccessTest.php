<?php

use App\Filament\Widgets\LatestQuestionIntegrityAuditOverview;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Support\QuestionIntegrityAuditService;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/question-integrity/gov_full_catalog'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/question-integrity/gov_full_catalog'));
});

test('admin login screen can be rendered', function () {
    $this->get('/admin/login')
        ->assertOk();
});

test('non admin users cannot access the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('admin users can access the admin panel', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Szybkie wejścia', false)
        ->assertSee('Użytkownicy', false);
});

test('admin dashboard shows the gov import guide section', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Import gov.pl', false)
        ->assertSee('Monitoring danych', false)
        ->assertSee('Dziennik audytu', false);
});

test('latest question integrity audit widget renders the current status', function () {
    $user = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'slug' => 'b',
        'name' => 'Kategoria B',
    ]);

    Question::factory()->booleanType()->create([
        'license_category_id' => $category->id,
        'external_id' => '2001',
        'prompt' => 'Czy ten znak oznacza stop?',
        'option_a' => 'Tak',
        'option_b' => 'Nie',
        'option_c' => null,
        'correct_answer' => 'A',
        'question_type' => 'boolean',
        'source' => 'gov.pl-mi',
        'metadata' => [
            'government_question_id' => '2001',
            'structure_scope' => 'PODSTAWOWY',
            'categories_original' => ['B'],
            'main_media_original' => 'stop.jpg',
        ],
    ]);

    $auditService = app(QuestionIntegrityAuditService::class);
    $auditService->auditCurrentCatalog('gov_full_catalog');
    $auditService->auditCurrentCatalog('gov_full_catalog');

    $this->actingAs($user);

    Livewire::test(LatestQuestionIntegrityAuditOverview::class)
        ->assertSee('Integralność pytań')
        ->assertSee('Zmiany standardowe')
        ->assertSee('Zmiany krytyczne')
        ->assertSee('Zielono', false);
});
