<?php

use App\Models\User;
use App\Models\UserReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest cannot submit a review', function () {
    $this->post(route('reviews.store'), [
        'rating' => 5,
        'content' => 'Bardzo wygodna platforma do codziennej nauki.',
    ])->assertRedirect(route('login'));
});

test('authenticated user can submit one review for moderation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reviews.store'), [
            'rating' => 5,
            'content' => 'Wyjaśnienia są czytelne, a powtórki naprawdę pomagają mi w nauce.',
        ])
        ->assertRedirect(route('home', absolute: false).'#opinie')
        ->assertSessionHas('review_success');

    $this->assertDatabaseHas('user_reviews', [
        'user_id' => $user->id,
        'rating' => 5,
        'status' => UserReview::STATUS_PENDING,
    ]);
});

test('editing a published review sends it back to moderation', function () {
    $user = User::factory()->create();
    $review = UserReview::query()->create([
        'user_id' => $user->id,
        'rating' => 4,
        'content' => 'Pierwsza wersja opinii o platformie i trybie nauki.',
        'status' => UserReview::STATUS_APPROVED,
        'published_at' => now(),
    ]);

    $this->actingAs($user)->post(route('reviews.store'), [
        'rating' => 5,
        'content' => 'Po kolejnych sesjach jeszcze bardziej doceniam wyjaśnienia i powtórki.',
    ])->assertRedirect(route('home', absolute: false).'#opinie');

    $review->refresh();

    expect($review->rating)->toBe(5)
        ->and($review->status)->toBe(UserReview::STATUS_PENDING)
        ->and($review->published_at)->toBeNull();
});

test('authenticated user can add social profile links to a review', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reviews.store'), [
            'rating' => 5,
            'content' => 'Bardzo wygodna platforma, którą chcę polecić również osobom z moich profili.',
            'social_links' => [
                'facebook' => 'facebook.com/prawkonaraz',
                'instagram' => 'https://instagram.com/prawkonaraz.pl',
                'website' => 'mojastrona.pl',
            ],
        ])
        ->assertRedirect(route('home', absolute: false).'#opinie')
        ->assertSessionHasNoErrors();

    $review = $user->review()->firstOrFail();

    expect($review->social_links)->toMatchArray([
        'facebook' => 'https://facebook.com/prawkonaraz',
        'instagram' => 'https://instagram.com/prawkonaraz.pl',
        'website' => 'https://mojastrona.pl',
    ]);
});

test('social platform fields reject links from another domain', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reviews.store'), [
            'rating' => 5,
            'content' => 'Ta opinia zawiera nieprawidłowo opisany link do zewnętrznego serwisu.',
            'social_links' => [
                'facebook' => 'https://example.com/nie-facebook',
            ],
        ])
        ->assertSessionHasErrors('social_links.facebook', errorBag: 'review');

    expect($user->review()->exists())->toBeFalse();
});

test('authenticated user can add a private exam result photo to a review', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reviews.store'), [
            'rating' => 5,
            'content' => 'Zdałem egzamin i dołączam zdjęcie wyniku po zasłonięciu danych osobowych.',
            'photo' => UploadedFile::fake()->image('wynik.png', 900, 700)->size(800),
            'photo_privacy_confirmed' => '1',
        ])
        ->assertRedirect(route('home', absolute: false).'#opinie');

    $review = $user->review()->firstOrFail();

    expect($review->photo_path)->not->toBeNull()
        ->and($review->photo_uploaded_at)->not->toBeNull()
        ->and($review->photo_privacy_confirmed_at)->not->toBeNull();

    Storage::disk('local')->assertExists($review->photo_path);
});

test('photo upload requires confirmation that personal data was covered', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reviews.store'), [
            'rating' => 5,
            'content' => 'To jest wystarczająco długa treść opinii przesyłanej razem ze zdjęciem.',
            'photo' => UploadedFile::fake()->image('wynik.jpg', 900, 700),
        ])
        ->assertSessionHasErrors('photo_privacy_confirmed', errorBag: 'review');

    expect($user->review()->exists())->toBeFalse();
});

test('editing a review can replace its photo and sends the review back to moderation', function () {
    Storage::fake('local');
    Storage::disk('local')->put('user-review-photos/old.jpg', 'old-photo');
    $user = User::factory()->create();
    $review = UserReview::query()->create([
        'user_id' => $user->id,
        'rating' => 4,
        'content' => 'Pierwsza wersja opinii z dołączonym zdjęciem wyniku egzaminu.',
        'photo_path' => 'user-review-photos/old.jpg',
        'photo_uploaded_at' => now(),
        'photo_privacy_confirmed_at' => now(),
        'status' => UserReview::STATUS_APPROVED,
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('reviews.store'), [
            'rating' => 5,
            'content' => 'Zaktualizowana opinia oraz nowsze zdjęcie potwierdzające wynik egzaminu.',
            'photo' => UploadedFile::fake()->image('nowy-wynik.jpg', 1000, 750),
            'photo_privacy_confirmed' => '1',
            'return_to' => 'reviews',
        ])
        ->assertRedirect(route('reviews.index', absolute: false).'#dodaj-opinie');

    $review->refresh();

    expect($review->photo_path)->not->toBe('user-review-photos/old.jpg')
        ->and($review->status)->toBe(UserReview::STATUS_PENDING)
        ->and($review->published_at)->toBeNull();

    Storage::disk('local')->assertMissing('user-review-photos/old.jpg');
    Storage::disk('local')->assertExists($review->photo_path);
});

test('review owner can remove the current photo while editing', function () {
    Storage::fake('local');
    Storage::disk('local')->put('user-review-photos/to-remove.jpg', 'photo');
    $user = User::factory()->create();
    $review = UserReview::query()->create([
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Opinia z fotografią, którą użytkownik chce później usunąć.',
        'photo_path' => 'user-review-photos/to-remove.jpg',
        'photo_uploaded_at' => now(),
        'photo_privacy_confirmed_at' => now(),
        'status' => UserReview::STATUS_PENDING,
    ]);

    $this->actingAs($user)->post(route('reviews.store'), [
        'rating' => 5,
        'content' => 'Opinia pozostaje, ale załączone wcześniej zdjęcie ma zostać usunięte.',
        'remove_photo' => '1',
    ]);

    expect($review->fresh()->photo_path)->toBeNull();
    Storage::disk('local')->assertMissing('user-review-photos/to-remove.jpg');
});

test('pending review photo is visible only to its owner or administrator', function () {
    Storage::fake('local');
    Storage::disk('local')->put('user-review-photos/private.jpg', 'private-photo');
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $review = UserReview::query()->create([
        'user_id' => $owner->id,
        'rating' => 5,
        'content' => 'Opinia oczekująca na zatwierdzenie wraz ze zdjęciem wyniku.',
        'photo_path' => 'user-review-photos/private.jpg',
        'photo_uploaded_at' => now(),
        'photo_privacy_confirmed_at' => now(),
        'status' => UserReview::STATUS_PENDING,
    ]);

    $this->get(route('reviews.photo', $review))->assertNotFound();
    $this->actingAs($otherUser)->get(route('reviews.photo', $review))->assertNotFound();
    $this->actingAs($owner)->get(route('reviews.photo', $review))
        ->assertOk()
        ->assertHeader('Cache-Control', 'private, no-store');
});

test('approved review photo is publicly visible', function () {
    Storage::fake('local');
    Storage::disk('local')->put('user-review-photos/public.jpg', 'public-photo');
    $user = User::factory()->create();
    $review = UserReview::query()->create([
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Zatwierdzona opinia ze zdjęciem potwierdzającym zdany egzamin.',
        'photo_path' => 'user-review-photos/public.jpg',
        'photo_uploaded_at' => now(),
        'photo_privacy_confirmed_at' => now(),
        'status' => UserReview::STATUS_APPROVED,
        'published_at' => now()->subMinute(),
    ]);

    $this->get(route('reviews.photo', $review))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

test('public reviews page lists approved reviews and offers editing to the owner', function () {
    $user = User::factory()->create(['name' => 'Anna Kowalska']);
    UserReview::query()->create([
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Pełna strona opinii pokazuje tę zatwierdzoną wypowiedź użytkownika.',
        'status' => UserReview::STATUS_APPROVED,
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('reviews.index'))
        ->assertOk()
        ->assertSeeText('Opinie o PrawkoNaRaz')
        ->assertSeeText('Anna K.')
        ->assertSeeText('Edytuj swoją opinię')
        ->assertSeeText('Zapisz zmiany');
});

test('approved review exposes safe user-generated social links', function () {
    $user = User::factory()->create(['name' => 'Anna Kowalska']);
    UserReview::query()->create([
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Opinia zawierająca opcjonalny publiczny profil społecznościowy użytkownika.',
        'social_links' => [
            'instagram' => 'https://instagram.com/anna',
        ],
        'status' => UserReview::STATUS_APPROVED,
        'published_at' => now(),
    ]);

    $this->get(route('reviews.index'))
        ->assertOk()
        ->assertSee('https://instagram.com/anna', escape: false)
        ->assertSee('nofollow ugc', escape: false);
});

test('profile exposes a direct review editing entry with the current moderation status', function () {
    $user = User::factory()->create();
    UserReview::query()->create([
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Moja opinia oczekuje na sprawdzenie i nadal mogę ją edytować.',
        'status' => UserReview::STATUS_PENDING,
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->where('review.exists', true)
            ->where('review.status', UserReview::STATUS_PENDING)
            ->where('review.status_label', 'Czeka na zatwierdzenie')
            ->where('review.edit_url', route('reviews.index', absolute: false).'#dodaj-opinie'));
});

test('home page shows approved reviews and hides pending reviews', function () {
    $publishedUser = User::factory()->create(['name' => 'Anna Kowalska']);
    $pendingUser = User::factory()->create(['name' => 'Jan Nowak']);

    UserReview::query()->create([
        'user_id' => $publishedUser->id,
        'rating' => 5,
        'content' => 'Dzięki trybowi powtórek łatwiej zapamiętuję najtrudniejsze pytania.',
        'status' => UserReview::STATUS_APPROVED,
        'published_at' => now(),
    ]);
    UserReview::query()->create([
        'user_id' => $pendingUser->id,
        'rating' => 4,
        'content' => 'Ta opinia nadal oczekuje na sprawdzenie przez administratora.',
        'status' => UserReview::STATUS_PENDING,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Anna K.')
        ->assertSeeText('Dzięki trybowi powtórek łatwiej zapamiętuję najtrudniejsze pytania.')
        ->assertDontSeeText('Ta opinia nadal oczekuje na sprawdzenie przez administratora.');
});

test('review validation requires a rating and a meaningful message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('home').'#opinie')
        ->post(route('reviews.store'), [
            'rating' => 6,
            'content' => 'Hi',
        ])
        ->assertSessionHasErrors(['rating', 'content'], errorBag: 'review');
});
