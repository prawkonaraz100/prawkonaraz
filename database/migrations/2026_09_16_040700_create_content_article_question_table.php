<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_article_question', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('content_articles')
                ->cascadeOnDelete();
            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete();
            $table->string('relation_type', 32);
            $table->smallInteger('sort_order')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'question_id']);
            $table->index(
                ['question_id', 'sort_order', 'article_id'],
                'content_article_question_reverse_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_article_question');
    }
};
