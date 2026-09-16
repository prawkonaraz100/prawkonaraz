<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_article_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('content_articles')
                ->cascadeOnDelete();
            $table->string('source_type', 32);
            $table->string('publisher')->nullable();
            $table->string('title', 500);
            $table->string('url', 2048)->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('accessed_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_official')->default(false);
            $table->boolean('is_publicly_cited')->default(true);
            $table->text('note')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['article_id', 'sort_order'], 'content_article_sources_article_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_article_sources');
    }
};
