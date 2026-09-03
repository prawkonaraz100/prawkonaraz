<?php

use App\Filament\Resources\TrafficSignQueryMapEntries\Pages\ListTrafficSignQueryMapEntries;
use App\Models\TrafficSignQueryMapEntry;
use App\Models\User;
use Livewire\Livewire;

test('admin sees query map resource columns and rollout bulk actions', function () {
    $admin = User::factory()->admin()->create();
    $entry = TrafficSignQueryMapEntry::factory()->create([
        'primary_query' => 'b-36 zakaz zatrzymywania się',
        'rollout_status' => TrafficSignQueryMapEntry::STATUS_BACKLOG,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListTrafficSignQueryMapEntries::class)
        ->assertTableColumnExists('primary_query')
        ->assertTableColumnStateSet('primary_query', $entry->primary_query, $entry)
        ->assertTableBulkActionExists('move_to_watchlist')
        ->assertTableBulkActionExists('mark_brief_ready')
        ->assertTableBulkActionExists('mark_drafting')
        ->assertTableBulkActionExists('mark_ready')
        ->assertTableBulkActionExists('mark_published')
        ->assertTableBulkActionExists('assign_batch');
});

test('admin can update rollout status and batch for query map entries', function () {
    $admin = User::factory()->admin()->create();
    $firstEntry = TrafficSignQueryMapEntry::factory()->create([
        'rollout_status' => TrafficSignQueryMapEntry::STATUS_BACKLOG,
        'batch_label' => null,
    ]);
    $secondEntry = TrafficSignQueryMapEntry::factory()->create([
        'rollout_status' => TrafficSignQueryMapEntry::STATUS_WATCHLIST,
        'batch_label' => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListTrafficSignQueryMapEntries::class)
        ->callTableBulkAction('mark_brief_ready', [$firstEntry, $secondEntry])
        ->assertHasNoTableBulkActionErrors()
        ->callTableBulkAction('assign_batch', [$firstEntry, $secondEntry], [
            'batch_label' => 'rollout-01',
        ])
        ->assertHasNoTableBulkActionErrors();

    expect($firstEntry->fresh()->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_BRIEF_READY);
    expect($secondEntry->fresh()->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_BRIEF_READY);
    expect($firstEntry->fresh()->batch_label)->toBe('rollout-01');
    expect($secondEntry->fresh()->batch_label)->toBe('rollout-01');
});
