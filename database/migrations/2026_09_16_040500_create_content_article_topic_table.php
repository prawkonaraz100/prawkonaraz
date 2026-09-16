<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_article_topic', function (Blueprint $table): void {
            $table->foreignId('article_id')
                ->constrained('content_articles')
                ->cascadeOnDelete();
            $table->foreignId('topic_id')
                ->constrained('content_topics')
                ->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['article_id', 'topic_id']);
            $table->index(['topic_id', 'article_id'], 'content_article_topic_reverse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_article_topic');
    }
};
