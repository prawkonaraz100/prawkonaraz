<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INACTIVITY_SECONDS = 300;

    public function up(): void
    {
        $this->updateInterruptedRecords(restore: false);
    }

    public function down(): void
    {
        $this->updateInterruptedRecords(restore: true);
    }

    private function updateInterruptedRecords(bool $restore): void
    {
        $records = DB::table('streak_records')
            ->join('streak_runs', 'streak_runs.id', '=', 'streak_records.best_run_id')
            ->where('streak_records.best_score', '>', 0)
            ->whereColumn('streak_records.best_score', 'streak_runs.score')
            ->whereIn('streak_runs.status', ['failed', 'completed'])
            ->whereNotNull('streak_runs.finished_at')
            ->select('streak_records.id', 'streak_records.best_run_id', 'streak_records.best_score',
                'streak_records.best_duration_seconds', 'streak_runs.started_at', 'streak_runs.finished_at');
        if ($restore) {
            $records->whereNull('streak_records.best_duration_seconds');
        } else {
            $records->where('streak_records.best_duration_seconds', '>=', self::INACTIVITY_SECONDS);
        }

        $records->chunkById(100, function ($batch) use ($restore): void {
            $answers = DB::table('streak_run_answers')
                ->whereIn('streak_run_id', $batch->pluck('best_run_id'))
                ->where('is_correct', true)
                ->orderBy('sequence')
                ->get(['streak_run_id', 'answered_at'])
                ->groupBy('streak_run_id');

            foreach ($batch as $record) {
                $times = $answers->get($record->best_run_id);
                if ($times === null || $times->count() !== (int) $record->best_score) {
                    continue;
                }

                $start = Carbon::parse($record->started_at)->getTimestamp();
                $previous = $start;
                $interrupted = false;
                foreach ($times as $answer) {
                    $answeredAt = Carbon::parse($answer->answered_at)->getTimestamp();
                    $interrupted = $interrupted || $answeredAt - $previous >= self::INACTIVITY_SECONDS;
                    $previous = $answeredAt;
                }
                if (! $interrupted || $previous > Carbon::parse($record->finished_at)->getTimestamp()) {
                    continue;
                }

                DB::table('streak_records')->where('id', $record->id)
                    ->where('best_run_id', $record->best_run_id)
                    ->where('best_score', $record->best_score)
                    ->where('best_duration_seconds', $record->best_duration_seconds)
                    ->update(['best_duration_seconds' => $restore ? $previous - $start : null]);
            }
        }, 'streak_records.id', 'id');
    }
};

