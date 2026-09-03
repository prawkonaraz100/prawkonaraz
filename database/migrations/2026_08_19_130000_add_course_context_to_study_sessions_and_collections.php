<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_collections', function (Blueprint $table): void {
            $table->boolean('is_available_to_learners')
                ->default(false)
                ->after('is_public');
            $table->index(
                ['is_active', 'is_available_to_learners'],
                'question_collections_learner_availability_idx',
            );
        });

        Schema::table('study_sessions', function (Blueprint $table): void {
            $table->foreignId('question_collection_id')
                ->nullable()
                ->after('license_category_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('question_module_id')
                ->nullable()
                ->after('question_collection_id')
                ->constrained()
                ->nullOnDelete();
            $table->index(
                ['question_collection_id', 'status', 'started_at', 'id'],
                'study_sessions_collection_status_started_id_idx',
            );
            $table->index(
                ['question_module_id', 'status'],
                'study_sessions_module_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table): void {
            $table->dropIndex('study_sessions_collection_status_started_id_idx');
            $table->dropIndex('study_sessions_module_status_idx');
            $table->dropConstrainedForeignId('question_module_id');
            $table->dropConstrainedForeignId('question_collection_id');
        });

        Schema::table('question_collections', function (Blueprint $table): void {
            $table->dropIndex('question_collections_learner_availability_idx');
            $table->dropColumn('is_available_to_learners');
        });
    }
};
