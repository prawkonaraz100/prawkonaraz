<?php

use App\Filament\Resources\UserReviews\Pages\EditUserReview;
use App\Filament\Resources\UserReviews\Pages\ListUserReviews;
use App\Filament\Resources\UserReviews\UserReviewResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserReview;
use Livewire\Livewire;

test('admin sees pending opinions and can open their edit form', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create([
        'name' => 'Autor Opinii',
        'email' => 'autor-opinii@example.test',
    ]);
    $review = UserReview::query()->create([
        'user_id' => $author->id,
        'rating' => 4,
        'content' => 'Opinia oczekująca na zatwierdzenie w panelu administratora.',
        'status' => UserReview::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->get(UserReviewResource::getUrl('index', panel: 'admin'))
        ->assertOk()
        ->assertSee('Opinie użytkowników', false)
        ->assertSee('Autor Opinii', false)
        ->assertSee('Czeka na zatwierdzenie', false);

    $this->actingAs($admin)
        ->get(UserReviewResource::getUrl('edit', ['record' => $review], panel: 'admin'))
        ->assertOk()
        ->assertSee('Edytuj opinię', false)
        ->assertSee('autor-opinii@example.test', false);

    $this->actingAs($admin);

    Livewire::test(ListUserReviews::class)
        ->assertTableActionExists('edit');
});

test('admin can edit and publish an opinion', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->create();
    $review = UserReview::query()->create([
        'user_id' => $author->id,
        'rating' => 3,
        'content' => 'Pierwotna treść opinii oczekującej na moderację administratora.',
        'status' => UserReview::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditUserReview::class, ['record' => $review->getRouteKey()])
        ->set('data.rating', 5)
        ->set('data.content', 'Poprawiona i zatwierdzona przez administratora treść opinii.')
        ->set('data.status', UserReview::STATUS_APPROVED)
        ->call('save')
        ->assertHasNoErrors();

    $review->refresh();

    expect($review->rating)->toBe(5)
        ->and($review->content)->toBe('Poprawiona i zatwierdzona przez administratora treść opinii.')
        ->and($review->status)->toBe(UserReview::STATUS_APPROVED)
        ->and($review->published_at)->not->toBeNull()
        ->and($review->moderated_by_user_id)->toBe($admin->id);

    expect(AuditLog::query()
        ->where('action', 'user_review.admin_updated')
        ->where('entity_id', (string) $review->id)
        ->exists())->toBeTrue();
});
