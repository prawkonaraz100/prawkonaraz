<?php

use App\Filament\Resources\ContentCategories\ContentCategoryResource;
use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('admin can access newsroom category resource with deterministic order and article counts', function () {
    $admin = User::factory()->admin()->create();

    $second = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy',
        'position' => 20,
        'is_active' => true,
    ]);

    $first = ContentCategory::factory()->create([
        'name' => 'Prawo jazdy',
        'slug' => 'prawo-jazdy',
        'position' => 10,
        'is_active' => true,
    ]);

    ContentArticle::factory()->published()->for($first, 'category')->create();
    ContentArticle::factory()->draft()->for($first, 'category')->create();

    $this->actingAs($admin)
        ->get(ContentCategoryResource::getUrl('index', panel: 'admin'))
        ->assertOk()
        ->assertSee('Kategorie newsroomu', false)
        ->assertSeeInOrder(['Prawo jazdy', 'Egzaminy'], false)
        ->assertSee('Wszystkie: 2', false)
        ->assertSee('Aktywnie dystrybuowane: 1', false)
        ->assertSee('Kolejność', false);

    $this->actingAs($admin)
        ->get(ContentCategoryResource::getUrl('view', ['record' => $first], panel: 'admin'))
        ->assertOk()
        ->assertSee('Podgląd kategorii newsroomu', false)
        ->assertSee('Wszystkie artykuły', false)
        ->assertSee('Aktywnie dystrybuowane', false);

    $this->actingAs($admin)
        ->get(ContentCategoryResource::getUrl('create', panel: 'admin'))
        ->assertOk()
        ->assertSee('Dodaj kategorię newsroomu', false);

    expect($second->fresh()->position)->toBe(20);
});

test('non admin cannot access newsroom category resource', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(ContentCategoryResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});

test('newsroom category slug is immutable after creation', function () {
    $category = ContentCategory::factory()->create([
        'slug' => 'prawo-jazdy',
    ]);

    expect(fn () => $category->update([
        'slug' => 'nowy-slug',
    ]))->toThrow(ValidationException::class, 'Slug kategorii jest niezmienny');

    expect($category->fresh()->slug)->toBe('prawo-jazdy');
});

test('newsroom category cannot be deactivated with actively distributed articles', function () {
    $category = ContentCategory::factory()->create([
        'is_active' => true,
    ]);

    ContentArticle::factory()->published()->for($category, 'category')->create();

    expect(fn () => $category->update([
        'is_active' => false,
    ]))->toThrow(ValidationException::class, 'Nie można dezaktywować kategorii');

    expect($category->fresh()->is_active)->toBeTrue();
});

test('newsroom category cannot be deactivated with archived publicly visible articles', function () {
    $category = ContentCategory::factory()->create([
        'is_active' => true,
    ]);

    ContentArticle::factory()->archived()->for($category, 'category')->create();

    expect(fn () => $category->update([
        'is_active' => false,
    ]))->toThrow(ValidationException::class, 'Nie można dezaktywować kategorii');

    expect($category->fresh()->is_active)->toBeTrue();
});

test('newsroom category with only draft articles can be deactivated but cannot be deleted', function () {
    $category = ContentCategory::factory()->create([
        'is_active' => true,
    ]);

    ContentArticle::factory()->draft()->for($category, 'category')->create();

    expect($category->canBeDeactivated())->toBeTrue();

    $category->update([
        'is_active' => false,
    ]);

    expect($category->fresh()->is_active)->toBeFalse()
        ->and($category->fresh()->canBeDeleted())->toBeFalse();

    expect(fn () => $category->fresh()->delete())
        ->toThrow(ValidationException::class, 'Nie można usunąć kategorii używanej przez artykuły');
});

test('empty newsroom category can be deactivated and deleted', function () {
    $category = ContentCategory::factory()->create([
        'is_active' => true,
    ]);

    $category->update([
        'is_active' => false,
    ]);

    expect($category->fresh()->is_active)->toBeFalse();

    expect($category->fresh()->delete())->toBeTrue()
        ->and(ContentCategory::query()->whereKey($category->getKey())->exists())->toBeFalse();
});
