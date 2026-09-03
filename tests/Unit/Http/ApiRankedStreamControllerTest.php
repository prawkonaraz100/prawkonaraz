<?php

use App\Http\Controllers\ApiRankedStreamController;
use App\Models\LicenseCategory;
use App\Models\RankedQueueEntry;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

test('queue left event is built when a previous queue snapshot disappears', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    $queueEntry = RankedQueueEntry::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'status' => 'queued',
        'joined_at' => now()->subSeconds(8),
        'payload' => [
            'source' => 'test',
        ],
    ]);

    $controller = new class extends ApiRankedStreamController
    {
        public function exposeQueueSnapshot(?RankedQueueEntry $queueEntry): ?array
        {
            return $this->queueSnapshot($queueEntry);
        }

        public function exposeQueueLeftEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, mixed $user): ?array
        {
            return $this->queueLeftEvent($previousQueueSnapshot, $currentQueueEntry, $user);
        }

        public function exposeQueueResumedEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, mixed $user): ?array
        {
            return $this->queueResumedEvent($previousQueueSnapshot, $currentQueueEntry, $user);
        }
    };

    $previousSnapshot = $controller->exposeQueueSnapshot($queueEntry);
    $event = $controller->exposeQueueLeftEvent($previousSnapshot, null, $user);

    expect($event)->not->toBeNull();
    expect($event['event'])->toBe('queue.left');
    expect($event['data']['user_id'])->toBe($user->getKey());
    expect($event['data']['queue_entry_id'])->toBe($queueEntry->getKey());
    expect($event['data']['previous_status'])->toBe('queued');
    expect($event['data']['category']['code'])->toBe('B');
    expect($event['data']['left_at'])->not->toBeNull();
});

test('queue left event is not built while queue entry is still active', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    $queueEntry = RankedQueueEntry::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'status' => 'queued',
        'joined_at' => now()->subSeconds(5),
        'payload' => [
            'source' => 'test',
        ],
    ]);

    $controller = new class extends ApiRankedStreamController
    {
        public function exposeQueueSnapshot(?RankedQueueEntry $queueEntry): ?array
        {
            return $this->queueSnapshot($queueEntry);
        }

        public function exposeQueueLeftEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, mixed $user): ?array
        {
            return $this->queueLeftEvent($previousQueueSnapshot, $currentQueueEntry, $user);
        }

        public function exposeQueueResumedEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, mixed $user): ?array
        {
            return $this->queueResumedEvent($previousQueueSnapshot, $currentQueueEntry, $user);
        }
    };

    $previousSnapshot = $controller->exposeQueueSnapshot($queueEntry);
    $event = $controller->exposeQueueLeftEvent($previousSnapshot, $queueEntry, $user);

    expect($event)->toBeNull();
});

test('queue resumed event is built when server full queue re-enters queued state', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    $queueEntry = RankedQueueEntry::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'status' => 'server_full',
        'joined_at' => now()->subSeconds(12),
        'payload' => [
            'source' => 'test',
            'recorded_at' => now()->subSeconds(5)->toIso8601String(),
        ],
    ]);

    $controller = new class extends ApiRankedStreamController
    {
        public function exposeQueueSnapshot(?RankedQueueEntry $queueEntry): ?array
        {
            return $this->queueSnapshot($queueEntry);
        }

        public function exposeQueueResumedEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, mixed $user): ?array
        {
            return $this->queueResumedEvent($previousQueueSnapshot, $currentQueueEntry, $user);
        }
    };

    $previousSnapshot = $controller->exposeQueueSnapshot($queueEntry);

    $queueEntry->forceFill([
        'status' => 'queued',
        'payload' => [
            'source' => 'test',
            'resumed_at' => now()->toIso8601String(),
        ],
    ])->save();

    $queueEntry->refresh();

    $event = $controller->exposeQueueResumedEvent($previousSnapshot, $queueEntry, $user);

    expect($event)->not->toBeNull();
    expect($event['event'])->toBe('queue.resumed');
    expect($event['data']['user_id'])->toBe($user->getKey());
    expect($event['data']['queue_entry_id'])->toBe($queueEntry->getKey());
    expect($event['data']['previous_status'])->toBe('server_full');
    expect($event['data']['status'])->toBe('queued');
    expect($event['data']['category']['code'])->toBe('B');
    expect($event['data']['resumed_at'])->not->toBeNull();
});

test('queue resumed event is not built without server full previous snapshot', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    $queueEntry = RankedQueueEntry::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'status' => 'queued',
        'joined_at' => now()->subSeconds(12),
        'payload' => [
            'source' => 'test',
            'resumed_at' => now()->toIso8601String(),
        ],
    ]);

    $controller = new class extends ApiRankedStreamController
    {
        public function exposeQueueSnapshot(?RankedQueueEntry $queueEntry): ?array
        {
            return $this->queueSnapshot($queueEntry);
        }

        public function exposeQueueResumedEvent(?array $previousQueueSnapshot, ?RankedQueueEntry $currentQueueEntry, mixed $user): ?array
        {
            return $this->queueResumedEvent($previousQueueSnapshot, $currentQueueEntry, $user);
        }
    };

    $previousSnapshot = $controller->exposeQueueSnapshot($queueEntry);
    $event = $controller->exposeQueueResumedEvent($previousSnapshot, $queueEntry, $user);

    expect($event)->toBeNull();
});
