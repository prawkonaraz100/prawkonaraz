<?php

use App\Filament\Resources\LicenseCategories\LicenseCategoryResource;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\User;
use App\Models\UserProfile;

test('admin users can access the license category resource with usage context', function () {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->withCode('B', 'Kategoria B')->create([
        'slug' => 'kategoria-b',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    Question::factory()->count(2)->create([
        'license_category_id' => $category->getKey(),
        'is_active' => true,
        'delivery_issue' => null,
    ]);

    StudySession::factory()->create([
        'license_category_id' => $category->getKey(),
        'created_at' => now()->subDays(10),
        'started_at' => now()->subDays(10),
        'completed_at' => now()->subDays(10),
    ]);

    StudySession::factory()->create([
        'license_category_id' => $category->getKey(),
        'created_at' => now()->subDays(120),
        'started_at' => now()->subDays(120),
        'completed_at' => now()->subDays(120),
    ]);

    UserProfile::factory()->create([
        'target_category_id' => $category->getKey(),
    ]);

    $this->actingAs($admin)
        ->get(LicenseCategoryResource::getUrl('index', panel: 'admin'))
        ->assertOk()
        ->assertSee('Kategorie', false)
        ->assertSee('Kategoria B', false)
        ->assertSee('Pytania: 2', false)
        ->assertSee('Sesje 90 dni: 1', false)
        ->assertSee('Profile: 1', false)
        ->assertSee('Retencja danych i znaczenie statystyk', false)
        ->assertSee('Ranking', false)
        ->assertSee('365 dni', false);
});

test('admin can view a single license category with usage summary', function () {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->withCode('C', 'Kategoria C')->create([
        'slug' => 'kategoria-c',
        'description' => 'Opis kategorii C',
        'is_active' => true,
    ]);

    Question::factory()->count(3)->create([
        'license_category_id' => $category->getKey(),
        'is_active' => true,
        'delivery_issue' => null,
    ]);

    StudySession::factory()->create([
        'license_category_id' => $category->getKey(),
        'created_at' => now()->subDays(14),
        'started_at' => now()->subDays(14),
        'completed_at' => now()->subDays(14),
    ]);

    $this->actingAs($admin)
        ->get(LicenseCategoryResource::getUrl('view', ['record' => $category], panel: 'admin'))
        ->assertOk()
        ->assertSee('Podgląd kategorii', false)
        ->assertSee('Opis kategorii C', false)
        ->assertSee('Wszystkie pytania', false)
        ->assertSee('3', false)
        ->assertSee('Sesje nauki (90 dni)', false)
        ->assertSee('Kategoria jest powiązana z 3 pytaniami · pojawiła się w 1 sesji z ostatnich 90 dni.', false);
});

test('non admin users cannot access the license category resource', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(LicenseCategoryResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});
