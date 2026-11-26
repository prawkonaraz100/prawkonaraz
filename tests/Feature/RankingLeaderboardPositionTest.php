<?php

use App\Models\LicenseCategory;
use App\Models\RankedPlayerRating;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Lista „Najlepsi gracze” pomija bieżącego gracza; gracze za nim mają miejsca przesunięte o jedno.
test('leaderboard positions skip the place held by the current user', function () {
    LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B', 'sort_order' => 10]);
    $user = User::factory()->withPurchasedAccess()->create(['name' => 'Ja']);

    foreach ([['Pierwszy', 1600], ['Trzeci', 1450], ['Czwarty', 1400]] as [$name, $rating]) {
        RankedPlayerRating::query()->create([
            'user_id' => User::factory()->create(['name' => $name])->getKey(),
            'rating' => $rating,
            'peak_rating' => $rating,
            'matches_played' => 3,
        ]);
    }

    RankedPlayerRating::query()->create([
        'user_id' => $user->getKey(),
        'rating' => 1500,
        'peak_rating' => 1500,
        'matches_played' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('session.ranking'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentUserRanking.position', 2)
            ->where('leaderboard.0.username', 'Pierwszy')
            ->where('leaderboard.0.position', 1)
            ->where('leaderboard.1.username', 'Trzeci')
            ->where('leaderboard.1.position', 3)
            ->where('leaderboard.2.username', 'Czwarty')
            ->where('leaderboard.2.position', 4)
        );
});
