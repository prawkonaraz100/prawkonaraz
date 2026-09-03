<?php

use App\Filament\Resources\ContentImportRuns\ContentImportRunResource;
use App\Models\ContentImportRun;
use App\Models\User;

test('admin users can access the content import run resource', function () {
    $admin = User::factory()->admin()->create();
    ContentImportRun::query()->create([
        'kind' => 'manifest_import',
        'identifier' => 'batch-123',
        'status' => 'ok',
        'questions_total' => 12,
        'summary' => ['selected_categories' => ['B']],
    ]);

    $this->actingAs($admin)
        ->get(ContentImportRunResource::getUrl('index', panel: 'admin'))
        ->assertOk()
        ->assertSee('Importy', false)
        ->assertSee('batch-123', false)
        ->assertSee('Import manifestu', false)
        ->assertSee('Retencja danych i wpływ na statystyki', false)
        ->assertSee('Operacyjne liczniki w panelu czytamy z okna', false)
        ->assertSee('365 dni', false);
});

test('content import run resource shows the integrity audit status badge', function () {
    $admin = User::factory()->admin()->create();

    ContentImportRun::query()->create([
        'kind' => 'manifest_series_import',
        'identifier' => 'batch-456',
        'status' => 'ok',
        'questions_total' => 12,
        'summary' => [
            'integrity_audit' => [
                'baseline' => false,
                'summary' => [
                    'current_total' => 12,
                    'added_total' => 0,
                    'removed_total' => 1,
                    'changed_total' => 1,
                    'critical_total' => 1,
                ],
            ],
        ],
    ]);

    $this->actingAs($admin)
        ->get(ContentImportRunResource::getUrl('index', panel: 'admin'))
        ->assertOk()
        ->assertSee('Czerwono', false);
});

test('content import run view shows operational sections instead of only raw json', function () {
    $admin = User::factory()->admin()->create();

    $run = ContentImportRun::query()->create([
        'kind' => 'manifest_series_import',
        'identifier' => 'batch-789',
        'status' => 'ok',
        'questions_total' => 12,
        'media_total' => 8,
        'rows_total' => 12,
        'warnings_count' => 1,
        'summary' => [
            'selected_categories' => ['B'],
            'warnings' => ['Brak plakatu dla jednego wideo.'],
            'chunks' => [
                [
                    'number' => 1,
                    'status' => 'ok',
                    'rows_total' => 12,
                    'questions_total' => 12,
                    'errors_count' => 0,
                ],
            ],
        ],
    ]);

    $this->actingAs($admin)
        ->get(ContentImportRunResource::getUrl('view', ['record' => $run], panel: 'admin'))
        ->assertOk()
        ->assertSee('Przebieg operacyjny', false)
        ->assertSee('Ścieżki i raporty', false)
        ->assertSee('Brak plakatu dla jednego wideo.', false);
});

test('non admin users cannot access the content import run resource', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(ContentImportRunResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});
