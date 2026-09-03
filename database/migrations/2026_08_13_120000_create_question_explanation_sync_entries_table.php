<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_explanation_sync_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_import_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_public_explanation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('external_id');
            $table->string('license_category_code', 16)->nullable();
            $table->char('source_public_explanation_hash', 64);
            $table->char('question_integrity_hash', 64);
            $table->char('previous_explanation_hash', 64);
            $table->char('new_explanation_hash', 64);
            $table->longText('previous_explanation')->nullable();
            $table->longText('new_explanation');
            $table->string('status', 40)->default('planned');
            $table->text('reason')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();

            $table->unique(['content_import_run_id', 'question_id']);
            $table->index(['content_import_run_id', 'status']);
            $table->index(['content_import_run_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_explanation_sync_entries');
    }
};
