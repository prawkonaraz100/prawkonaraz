<?php

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use App\Models\User;

test('admin users can access the question media management page', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create([
        'external_id' => 'B-009999',
        'prompt' => 'Czy mozesz sprawdzic panel uploadu mediow?',
    ]);

    $this->actingAs($admin)
        ->get(QuestionResource::getUrl('media', ['record' => $question], panel: 'admin'))
        ->assertOk()
        ->assertSee('Dodaj media', false)
        ->assertSee('Aktualne media', false)
        ->assertSee('B-009999', false);
});

test('non admin users cannot access the question media management page', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create();

    $this->actingAs($user)
        ->get(QuestionResource::getUrl('media', ['record' => $question], panel: 'admin'))
        ->assertForbidden();
});
