<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_media', function (Blueprint $table) {
            $table->id();
            // Foreign key is added in a follow-up migration so fresh PostgreSQL
            // bootstraps do not depend on alphabetical execution of same-second migrations.
            $table->foreignId('question_id');
            $table->string('kind', 20);
            $table->string('disk', 50)->default('s3');
            $table->string('path');
            $table->string('poster_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_media');
    }
};
