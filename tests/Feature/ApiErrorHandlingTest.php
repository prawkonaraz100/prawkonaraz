<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\User;
use Illuminate\Support\Facades\Route;

test('api responses include and preserve request ids', function () {
    $this->withHeaders([
        'X-Request-Id' => 'req-health-123',
    ])->getJson(route('api.v1.health'))
        ->assertOk()
        ->assertHeader('X-Request-Id', 'req-health-123')
        ->assertJsonPath('data.status', 'ok');
});

test('unknown api routes return a standardized not found envelope', function () {
    $response = $this->getJson('/api/v1/does-not-exist');

    $requestId = $response->headers->get('X-Request-Id');

    $response->assertNotFound()
        ->assertHeader('X-Request-Id')
        ->assertJsonPath('error.code', 'NOT_FOUND')
        ->assertJsonPath('meta.request_id', $requestId);

    expect($requestId)->not->toBeEmpty();
});

test('method not allowed on api route returns a standardized envelope', function () {
    $this->getJson(route('api.v1.sessions.store'))
        ->assertStatus(405)
        ->assertJsonPath('error.code', 'METHOD_NOT_ALLOWED')
        ->assertHeader('X-Request-Id');
});

test('unauthenticated api requests return a standardized unauthorized envelope', function () {
    $this->getJson(route('api.v1.me.profile.show'))
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHORIZED')
        ->assertHeader('X-Request-Id');
});

test('form request authorization failures return a standardized forbidden envelope', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);

    $this->actingAs($user)
        ->postJson(route('api.v1.admin.questions.store'), [
            'license_category_id' => $category->getKey(),
            'external_id' => 'B-999',
            'prompt' => 'Czy mozna zawracac na autostradzie?',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'FORBIDDEN')
        ->assertHeader('X-Request-Id');
});

test('abort based admin authorization failures return a standardized forbidden envelope', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create();
    $media = QuestionMedia::factory()
        ->for($question)
        ->create();

    $this->actingAs($user)
        ->deleteJson(route('api.v1.admin.media.destroy', $media))
        ->assertForbidden()
        ->assertJsonPath('error.code', 'FORBIDDEN')
        ->assertHeader('X-Request-Id');
});

test('validation exceptions keep laravel validation payloads for api clients', function () {
    $user = User::factory()->withPurchasedAccess()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category_id', 'mode'])
        ->assertHeader('X-Request-Id');

    expect($response->json('error'))->toBeNull();
});

test('unexpected api exceptions return a standardized internal error envelope', function () {
    Route::middleware('web')->get('/api/v1/testing/fail', function () {
        throw new RuntimeException('boom');
    });

    $response = $this->getJson('/api/v1/testing/fail');
    $requestId = $response->headers->get('X-Request-Id');

    $response->assertStatus(500)
        ->assertJsonPath('error.code', 'INTERNAL_ERROR')
        ->assertJsonPath('meta.request_id', $requestId)
        ->assertHeader('X-Request-Id');
});
