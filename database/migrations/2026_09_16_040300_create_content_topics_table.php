<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_topics', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->text('description');
            $table->string('status', 32)->default('draft');
            $table->foreignId('featured_article_id')
                ->nullable()
                ->constrained('content_articles')
                ->nullOnDelete();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at'], 'content_topics_status_published_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_topics');
    }
};
