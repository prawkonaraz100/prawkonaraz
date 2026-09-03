<?php

use App\Filament\Resources\TrafficSigns\Pages\ListTrafficSigns;
use App\Models\TrafficSign;
use App\Models\User;
use Livewire\Livewire;

test('admin sees publication checklist column and content ops bulk actions for traffic signs', function () {
    $admin = User::factory()->admin()->create();
    $sign = TrafficSign::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListTrafficSigns::class)
        ->assertTableColumnExists('publication_checklist')
        ->assertTableColumnStateSet('publication_checklist', $sign->publicationChecklistCompletionLabel(), $sign)
        ->assertTableBulkActionExists('send_to_review')
        ->assertTableBulkActionExists('mark_needs_review')
        ->assertTableBulkActionExists('confirm_sources')
        ->assertTableBulkActionExists('schedule_freshness_review')
        ->assertTableBulkActionExists('publish_ready')
        ->assertTableBulkActionExists('withdraw_publication');
});

test('admin can publish only traffic signs that pass the publication checklist', function () {
    $admin = User::factory()->admin()->create();

    $readySign = TrafficSign::factory()->create([
        'source_checked_at' => now(),
        'freshness_review_due_at' => now()->addMonths(6),
    ]);

    $incompleteSign = TrafficSign::factory()->create([
        'source_checked_at' => null,
        'freshness_review_due_at' => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListTrafficSigns::class)
        ->callTableBulkAction('publish_ready', [$readySign, $incompleteSign])
        ->assertHasNoTableBulkActionErrors();

    expect($readySign->fresh()->is_published)->toBeTrue();
    expect($readySign->fresh()->workflow_status)->toBe(TrafficSign::WORKFLOW_PUBLISHED);
    expect($readySign->fresh()->published_at)->not->toBeNull();

    expect($incompleteSign->fresh()->is_published)->toBeFalse();
    expect($incompleteSign->fresh()->workflow_status)->toBe(TrafficSign::WORKFLOW_DRAFT);
    expect($incompleteSign->fresh()->published_at)->toBeNull();
});

test('admin can batch-confirm sources and schedule freshness review for traffic signs', function () {
    $admin = User::factory()->admin()->create();
    $firstSign = TrafficSign::factory()->create();
    $secondSign = TrafficSign::factory()->create();
    $dueAt = now()->addMonths(3)->startOfHour();

    $this->actingAs($admin);

    Livewire::test(ListTrafficSigns::class)
        ->callTableBulkAction('confirm_sources', [$firstSign, $secondSign])
        ->assertHasNoTableBulkActionErrors()
        ->callTableBulkAction('schedule_freshness_review', [$firstSign, $secondSign], [
            'freshness_review_due_at' => $dueAt->format('Y-m-d H:i:s'),
        ])
        ->assertHasNoTableBulkActionErrors();

    expect($firstSign->fresh()->source_checked_at)->not->toBeNull();
    expect($secondSign->fresh()->source_checked_at)->not->toBeNull();
    expect($firstSign->fresh()->freshness_review_due_at?->format('Y-m-d H:i:s'))->toBe($dueAt->format('Y-m-d H:i:s'));
    expect($secondSign->fresh()->freshness_review_due_at?->format('Y-m-d H:i:s'))->toBe($dueAt->format('Y-m-d H:i:s'));
});
