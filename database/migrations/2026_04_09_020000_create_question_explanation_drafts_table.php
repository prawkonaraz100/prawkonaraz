<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_explanation_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->default('pj360');
            $table->string('external_id');
            $table->longText('prompt');
            $table->longText('draft_text');
            $table->text('source_summary')->nullable();
            $table->json('categories');
            $table->string('question_type', 32)->nullable();
            $table->string('question_media_kind', 32)->nullable();
            $table->string('structure_scope', 32)->nullable();
            $table->text('accepted_answer')->nullable();
            $table->string('accepted_answer_label', 1)->nullable();
            $table->string('source_queue', 32)->nullable();
            $table->string('source_question_id')->nullable();
            $table->text('source_url')->nullable();
            $table->string('tier_b_decision', 64)->nullable();
            $table->text('tier_b_note')->nullable();
            $table->string('resolution_method', 64)->nullable();
            $table->json('quality_flags')->nullable();
            $table->json('staging_flags')->nullable();
            $table->json('local_category_codes')->nullable();
            $table->json('missing_category_codes')->nullable();
            $table->json('local_question_ids')->nullable();
            $table->unsignedInteger('local_question_count')->default(0);
            $table->unsignedInteger('local_existing_explanation_count')->default(0);
            $table->string('status', 32)->default('staged');
            $table->string('staging_issue', 64)->nullable();
            $table->json('source_payload');
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['source', 'status']);
            $table->index(['source', 'source_queue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_explanation_drafts');
    }
};
