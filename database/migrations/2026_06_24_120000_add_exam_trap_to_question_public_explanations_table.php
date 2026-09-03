<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_public_explanations', function (Blueprint $table): void {
            $table->text('exam_trap')->nullable()->after('body');
        });

        DB::table('question_public_explanations')
            ->where('external_id', '99')
            ->whereNull('exam_trap')
            ->update([
                'exam_trap' => 'W tym pytaniu nie chodzi tylko o obecność tramwaju. Kluczowe jest to, że tramwaj znajduje się na oznaczonym przystanku bez wysepki dla pasażerów. W takiej sytuacji kierujący musi zatrzymać pojazd, a nie tylko zwolnić.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('question_public_explanations', function (Blueprint $table): void {
            $table->dropColumn('exam_trap');
        });
    }
};
