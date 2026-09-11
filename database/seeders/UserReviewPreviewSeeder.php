<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserReview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserReviewPreviewSeeder extends Seeder
{
    /**
     * Add clearly isolated, local preview reviews for visual development.
     */
    public function run(): void
    {
        $samples = [
            ['Anna Kowalska', 5, 'Najbardziej pomagają mi krótkie wyjaśnienia po odpowiedzi. Wreszcie rozumiem zasady, zamiast tylko zapamiętywać układ pytań.'],
            ['Michał Wiśniewski', 5, 'Tryb skupienia jest bardzo czytelny, a powtórki błędów oszczędzają mnóstwo czasu. Nauka idzie znacznie sprawniej.'],
            ['Karolina Nowak', 5, 'Podoba mi się, że mogę przejść pytania szybko, a później wrócić tylko do tych, które sprawiły mi problem.'],
            ['Tomasz Zieliński', 5, 'Platforma jest prosta w obsłudze i dobrze działa na telefonie. Statystyki jasno pokazują, nad czym muszę jeszcze popracować.'],
            ['Julia Kamińska', 5, 'Dzięki regularnym sesjom i wyjaśnieniom czuję się dużo pewniej przed egzaminem. Wszystko mam w jednym miejscu.'],
            ['Paweł Lewandowski', 5, 'Skróty klawiaturowe i automatyczne przejście między pytaniami pozwalają utrzymać tempo bez zbędnego klikania.'],
        ];

        foreach ($samples as $index => [$name, $rating, $content]) {
            $user = User::query()->updateOrCreate(
                ['email' => 'preview-review-'.($index + 1).'@prawkonaraz.local'],
                [
                    'name' => $name,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(40)),
                    'password_login_enabled' => false,
                    'is_test_account' => true,
                ],
            );

            UserReview::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'rating' => $rating,
                    'content' => $content,
                    'status' => UserReview::STATUS_APPROVED,
                    'published_at' => now()->subDays($index + 2),
                    'moderated_at' => now(),
                ],
            );
        }
    }
}
