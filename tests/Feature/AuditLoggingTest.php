<?php

use App\Models\AuditLog;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('admin question upsert writes an audit log entry', function () {
    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $this->actingAs($admin)
        ->withHeaders([
            'X-Request-Id' => 'audit-question-req',
        ])
        ->postJson(route('api.v1.admin.questions.store'), [
            'license_category_id' => $category->getKey(),
            'external_id' => 'B-501',
            'prompt' => 'Czy wolno zatrzymac sie na przejsciu dla pieszych?',
            'explanation' => 'Nie wolno blokowac przejscia dla pieszych.',
            'option_a' => 'Tak',
            'option_b' => 'Nie',
            'correct_answer' => 'b',
            'difficulty' => 3,
            'points' => 2,
        ])
        ->assertCreated();

    $question = Question::query()->where('external_id', 'B-501')->firstOrFail();
    $auditLog = AuditLog::query()
        ->where('action', 'admin.question.created')
        ->where('entity_type', 'question')
        ->where('entity_id', (string) $question->getKey())
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog?->actor_user_id)->toBe($admin->getKey());
    expect($auditLog?->request_id)->toBe('audit-question-req');
    expect($auditLog?->metadata)->toMatchArray([
        'source' => 'admin_api',
        'external_id' => 'B-501',
        'license_category_id' => $category->getKey(),
        'media_count' => 0,
    ]);
});

test('admin media mutations write audit log entries', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.public_disk' => 'r2',
        'media.public_base_url' => 'https://media.example.test',
    ]);

    $admin = User::factory()->admin()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
    ]);
    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'B-000303',
        ]);

    $presign = $this->actingAs($admin)
        ->postJson(route('api.v1.admin.media.presign'), [
            'question_id' => $question->getKey(),
            'kind' => 'image',
            'mime_type' => 'image/webp',
            'bytes' => 2048,
            'variant' => 'full',
        ])
        ->assertOk();

    $path = (string) $presign->json('data.path');
    Storage::disk('r2')->put($path, 'image-binary');

    $confirm = $this->actingAs($admin)
        ->withHeaders([
            'X-Request-Id' => 'audit-media-confirm-req',
        ])
        ->postJson(route('api.v1.admin.media.confirm'), [
            'upload_token' => $presign->json('data.upload_token'),
            'sort_order' => 0,
        ])
        ->assertCreated();

    $mediaId = (string) $confirm->json('data.id');

    $this->actingAs($admin)
        ->withHeaders([
            'X-Request-Id' => 'audit-media-reorder-req',
        ])
        ->patchJson(route('api.v1.admin.questions.media.reorder', $question), [
            'media_ids' => [(int) $mediaId],
        ])
        ->assertOk();

    $this->actingAs($admin)
        ->withHeaders([
            'X-Request-Id' => 'audit-media-delete-req',
        ])
        ->deleteJson(route('api.v1.admin.media.destroy', ['questionMedia' => $mediaId]))
        ->assertOk();

    $logs = AuditLog::query()
        ->whereIn('action', [
            'admin.question_media.created',
            'admin.question_media.reordered',
            'admin.question_media.deleted',
        ])
        ->orderBy('id')
        ->get();

    expect($logs)->toHaveCount(3);
    expect($logs->pluck('action')->all())->toBe([
        'admin.question_media.created',
        'admin.question_media.reordered',
        'admin.question_media.deleted',
    ]);
    expect($logs[0]->request_id)->toBe('audit-media-confirm-req');
    expect($logs[0]->metadata)->toMatchArray([
        'source' => 'admin_api',
        'question_id' => $question->getKey(),
        'path' => $path,
        'kind' => 'image',
        'variant' => 'full',
    ]);
    expect($logs[1]->entity_type)->toBe('question');
    expect($logs[1]->entity_id)->toBe((string) $question->getKey());
    expect($logs[1]->request_id)->toBe('audit-media-reorder-req');
    expect($logs[2]->request_id)->toBe('audit-media-delete-req');
    expect($logs[2]->metadata)->toMatchArray([
        'source' => 'admin_api',
        'question_id' => $question->getKey(),
        'path' => $path,
        'kind' => 'image',
        'variant' => 'full',
    ]);
});
