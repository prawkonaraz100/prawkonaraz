<?php

use App\Filament\Resources\ContentCategories\ContentCategoryResource;
use App\Filament\Resources\ContentCategories\Pages\CreateContentCategory;
use App\Filament\Resources\ContentCategories\Pages\EditContentCategory;
use App\Filament\Resources\ContentCategories\Pages\ListContentCategories;
use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('admin can access content category resource with article counts', function () {
    $admin = User::factory()->admin()->create();
    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy',
        'position' => 20,
    ]);

    ContentArticle::factory()->for($category, 'category')->create();
    ContentArticle::factory()->published()->for($category, 'category')->create();

    $this->actingAs($admin);

    $record = ContentCategoryResource::getEloquentQuery()
        ->whereKey($category->getKey())
        ->firstOrFail();

    expect((int) $record->articles_count)->toBe(2)
        ->and((int) $record->publicly_visible_articles_count)->toBe(1)
        ->and((int) $record->actively_distributed_articles_count)->toBe(1);

    Livewire::test(ListContentCategories::class)
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('articles_summary')
        ->assertTableColumnExists('is_active')
        ->assertTableColumnExists('position')
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit')
        ->assertTableActionExists('delete');
});

test('non admin cannot access content category resource', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(ContentCategoryResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});

test('admin can create and edit mutable content category fields', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateContentCategory::class)
        ->set('data.name', 'Rynek OSK')
        ->set('data.slug', 'rynek-osk')
        ->set('data.description', 'Materiały dla szkół jazdy.')
        ->set('data.position', 70)
        ->set('data.is_active', true)
        ->set('data.seo_title', 'Rynek OSK')
        ->set('data.seo_description', 'Aktualności i materiały dla OSK.')
        ->call('create')
        ->assertHasNoErrors();

    $category = ContentCategory::query()->where('slug', 'rynek-osk')->firstOrFail();

    Livewire::test(EditContentCategory::class, ['record' => $category->getRouteKey()])
        ->set('data.name', 'OSK i szkolenie')
        ->set('data.position', 80)
        ->set('data.description', 'Zaktualizowany opis kategorii.')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh()->name)->toBe('OSK i szkolenie')
        ->and($category->fresh()->position)->toBe(80)
        ->and($category->fresh()->slug)->toBe('rynek-osk');
});

test('category slug must match route contract and is immutable after creation', function () {
    expect(fn () => ContentCategory::factory()->create([
        'slug' => 'Niepoprawny Slug',
    ]))->toThrow(ValidationException::class);

    $category = ContentCategory::factory()->create([
        'slug' => 'przepisy',
    ]);

    $category->slug = 'nowe-przepisy';

    expect(fn () => $category->save())
        ->toThrow(ValidationException::class);

    expect($category->fresh()->slug)->toBe('przepisy');
});

test('category with public articles cannot be deactivated', function () {
    $category = ContentCategory::factory()->create([
        'slug' => 'kierowcy',
        'is_active' => true,
    ]);

    ContentArticle::factory()->published()->for($category, 'category')->create();

    $category->is_active = false;

    expect(fn () => $category->save())
        ->toThrow(ValidationException::class);

    expect($category->fresh()->is_active)->toBeTrue();
});

test('admin edit form surfaces deactivation guard on the active field', function () {
    $admin = User::factory()->admin()->create();
    $category = ContentCategory::factory()->create([
        'slug' => 'osk',
        'is_active' => true,
    ]);

    ContentArticle::factory()->published()->for($category, 'category')->create();

    $this->actingAs($admin);

    Livewire::test(EditContentCategory::class, ['record' => $category->getRouteKey()])
        ->set('data.is_active', false)
        ->call('save')
        ->assertHasErrors(['data.is_active']);

    expect($category->fresh()->is_active)->toBeTrue();
});

test('category without public articles can be deactivated', function () {
    $category = ContentCategory::factory()->create([
        'slug' => 'word',
        'is_active' => true,
    ]);

    ContentArticle::factory()->for($category, 'category')->create();

    $category->update([
        'is_active' => false,
    ]);

    expect($category->fresh()->is_active)->toBeFalse();
});

test('category used by any article cannot be deleted while empty category can be deleted', function () {
    $used = ContentCategory::factory()->create([
        'slug' => 'prawo-jazdy',
    ]);
    $empty = ContentCategory::factory()->create([
        'slug' => 'egzaminy',
    ]);

    ContentArticle::factory()->for($used, 'category')->create();

    expect(fn () => $used->delete())
        ->toThrow(ValidationException::class);

    expect($used->fresh())->not->toBeNull();

    $empty->delete();

    expect(ContentCategory::query()->whereKey($empty->getKey())->exists())->toBeFalse();
});

test('category invariants ignore stale preloaded article counts', function () {
    $category = ContentCategory::factory()->create([
        'slug' => 'bezpieczenstwo',
        'is_active' => true,
    ]);

    $staleRecord = ContentCategoryResource::getEloquentQuery()
        ->whereKey($category->getKey())
        ->firstOrFail();

    expect((int) $staleRecord->articles_count)->toBe(0)
        ->and((int) $staleRecord->publicly_visible_articles_count)->toBe(0)
        ->and((int) $staleRecord->actively_distributed_articles_count)->toBe(0);

    ContentArticle::factory()->published()->for($category, 'category')->create();

    $staleRecord->is_active = false;

    expect(fn () => $staleRecord->save())
        ->toThrow(ValidationException::class);

    expect($category->fresh()->is_active)->toBeTrue();
});

test('category delete guard ignores stale preloaded article count', function () {
    $category = ContentCategory::factory()->create([
        'slug' => 'metodyka',
    ]);

    $staleRecord = ContentCategoryResource::getEloquentQuery()
        ->whereKey($category->getKey())
        ->firstOrFail();

    expect((int) $staleRecord->articles_count)->toBe(0);

    ContentArticle::factory()->for($category, 'category')->create();

    expect(fn () => $staleRecord->delete())
        ->toThrow(ValidationException::class);

    expect(ContentCategory::query()->whereKey($category->getKey())->exists())->toBeTrue();
});
