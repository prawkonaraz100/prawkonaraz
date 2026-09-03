<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_question_explanation_sign_overrides', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id');
            $table->string('source_scope');
            $table->string('action', 20);
            $table->string('detected_code', 32)->nullable();
            $table->string('anchor_text', 255)->nullable();
            $table->foreignId('traffic_sign_id')->nullable()->constrained('traffic_signs')->nullOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['external_id', 'source_scope'], 'shared_question_expl_sign_override_scope_idx');
            $table->index(['external_id', 'source_scope', 'is_active'], 'shared_question_expl_sign_override_active_idx');
            $table->index(['traffic_sign_id']);
        });

        Schema::create('question_explanation_sign_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20);
            $table->string('detected_code', 32)->nullable();
            $table->string('anchor_text', 255)->nullable();
            $table->foreignId('traffic_sign_id')->nullable()->constrained('traffic_signs')->nullOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['question_id', 'is_active'], 'question_expl_sign_override_active_idx');
            $table->index(['traffic_sign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_explanation_sign_overrides');
        Schema::dropIfExists('shared_question_explanation_sign_overrides');
    }
};
