<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tryb rankingowy, etap 0 (2026-11-25):
 * - elapsed_ms: czas od startu meczu do odpowiedzi, liczony na serwerze
 *   (podstawa czasu odpowiedzi i rozstrzygania remisu zamiast wartości z przeglądarki),
 * - jeden aktywny wpis w kolejce na użytkownika (częściowy unikalny indeks).
 */
return new class extends Migration
{
    private const ACTIVE_QUEUE_INDEX = 'ranked_queue_entries_one_active_per_user';

    public function up(): void
    {
        if (! Schema::hasColumn('ranked_match_answers', 'elapsed_ms')) {
            Schema::table('ranked_match_answers', function (Blueprint $table) {
                $table->unsignedInteger('elapsed_ms')->nullable()->after('response_time_ms');
            });
        }

        if (! $this->supportsPartialIndexes()) {
            return;
        }

        $this->cancelDuplicateActiveQueueEntries();

        DB::statement(sprintf(
            "CREATE UNIQUE INDEX IF NOT EXISTS %s ON ranked_queue_entries (user_id) WHERE status IN ('queued', 'matched', 'server_full')",
            self::ACTIVE_QUEUE_INDEX,
        ));
    }

    public function down(): void
    {
        if ($this->supportsPartialIndexes()) {
            DB::statement(sprintf('DROP INDEX IF EXISTS %s', self::ACTIVE_QUEUE_INDEX));
        }

        if (Schema::hasColumn('ranked_match_answers', 'elapsed_ms')) {
            Schema::table('ranked_match_answers', function (Blueprint $table) {
                $table->dropColumn('elapsed_ms');
            });
        }
    }

    private function supportsPartialIndexes(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['pgsql', 'sqlite'], true);
    }

    /**
     * Przed założeniem indeksu zostawia najnowszy aktywny wpis każdego użytkownika,
     * a starsze aktywne wpisy oznacza jako anulowane.
     */
    private function cancelDuplicateActiveQueueEntries(): void
    {
        $activeStatuses = ['queued', 'matched', 'server_full'];

        $duplicateUserIds = DB::table('ranked_queue_entries')
            ->whereIn('status', $activeStatuses)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $keepId = DB::table('ranked_queue_entries')
                ->where('user_id', $userId)
                ->whereIn('status', $activeStatuses)
                ->max('id');

            DB::table('ranked_queue_entries')
                ->where('user_id', $userId)
                ->whereIn('status', $activeStatuses)
                ->where('id', '!=', $keepId)
                ->update([
                    'status' => 'cancelled',
                    'left_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }
};
