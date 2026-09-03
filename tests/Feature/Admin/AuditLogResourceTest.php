<?php

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;

test('admin users can access the audit log resource with humanized labels', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Anna Admin',
        'email' => 'anna@example.com',
    ]);

    AuditLog::factory()->create([
        'actor_user_id' => $admin->getKey(),
        'action' => 'admin.question.created',
        'entity_type' => 'question',
        'entity_id' => '101',
        'ip_address' => '192.168.10.11',
        'metadata' => [
            'source' => 'admin_api',
            'external_id' => 'GOV-101',
            'question_type' => 'boolean',
            'media_count' => 1,
        ],
    ]);

    $this->actingAs($admin)
        ->get(AuditLogResource::getUrl('index', panel: 'admin'))
        ->assertOk()
        ->assertSee('Dziennik audytu', false)
        ->assertSee('Dodano pytanie', false)
        ->assertSee('Anna Admin', false)
        ->assertSee('Panel admina', false);
});

test('admin can open a single audit log entry with operator details and metadata', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Anna Admin',
        'email' => 'anna@example.com',
    ]);

    $auditLog = AuditLog::factory()->create([
        'actor_user_id' => $admin->getKey(),
        'action' => 'user.banned',
        'entity_type' => 'user',
        'entity_id' => '55',
        'ip_address' => '10.0.0.7',
        'request_id' => 'req-123',
        'metadata' => [
            'source' => 'admin_api',
            'reason' => 'Powtarzające się nadużycia',
        ],
    ]);

    $this->actingAs($admin)
        ->get(AuditLogResource::getUrl('view', ['record' => $auditLog], panel: 'admin'))
        ->assertOk()
        ->assertSee('Szczegóły wpisu audytu', false)
        ->assertSee('Zablokowano użytkownika', false)
        ->assertSee('Powtarzające się nadużycia', false)
        ->assertSee('req-123', false);
});

test('non admin users cannot access the audit log resource', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(AuditLogResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});
