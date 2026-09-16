<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_article_redirects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('content_articles')
                ->cascadeOnDelete();
            $table->string('from_path', 1024)->unique();
            $table->string('to_path', 1024);
            $table->smallInteger('http_status')->default(301);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_article_redirects');
    }
};
