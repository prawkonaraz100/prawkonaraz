<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_article_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('content_articles')
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained('content_tags')
                ->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['article_id', 'tag_id']);
            $table->index(['tag_id', 'article_id'], 'content_article_tag_reverse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_article_tag');
    }
};
