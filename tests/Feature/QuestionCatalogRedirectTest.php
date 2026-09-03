<?php

use App\Models\User;

test('question catalog route redirects authenticated users to the session page', function () {
    $user = User::factory()->withPurchasedAccess()->create();

    $this->actingAs($user)
        ->get(route('questions.index'))
        ->assertRedirect(route('session.index'));
});

test('question catalog route redirects users without access to activation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('questions.index'))
        ->assertRedirect(route('access.activate'));
});

test('question catalog route still requires authentication', function () {
    $this->get(route('questions.index'))
        ->assertRedirect(route('login'));
});
