<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_videos', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('youtube_url', 2048);
            $table->string('youtube_video_id', 32)->index();
            $table->string('thumbnail_path', 2048)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->date('published_on')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_videos');
    }
};
