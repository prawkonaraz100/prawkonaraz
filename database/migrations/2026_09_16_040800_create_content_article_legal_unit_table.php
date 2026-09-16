<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_article_legal_unit', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('content_articles')
                ->cascadeOnDelete();
            $table->foreignId('legal_unit_id')
                ->constrained('legal_units')
                ->cascadeOnDelete();
            $table->string('relation_type', 32);
            $table->smallInteger('sort_order')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'legal_unit_id']);
            $table->index(
                ['legal_unit_id', 'sort_order', 'article_id'],
                'content_article_legal_unit_reverse_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_article_legal_unit');
    }
};
