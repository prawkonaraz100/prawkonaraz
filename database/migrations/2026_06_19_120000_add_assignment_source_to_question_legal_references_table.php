<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_legal_references', function (Blueprint $table): void {
            $table->string('assignment_source', 32)
                ->default('seed')
                ->after('internal_note')
                ->index();
        });

        DB::table('question_legal_references')
            ->where('internal_note', 'like', 'Ręczna edycja%')
            ->update(['assignment_source' => 'manual']);
    }

    public function down(): void
    {
        Schema::table('question_legal_references', function (Blueprint $table): void {
            $table->dropIndex(['assignment_source']);
            $table->dropColumn('assignment_source');
        });
    }
};
