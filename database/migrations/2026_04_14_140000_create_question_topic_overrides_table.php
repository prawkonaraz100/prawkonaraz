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
        Schema::create('question_topic_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('license_category_code', 16);
            $table->string('source');
            $table->string('external_id');
            $table->string('question_topic_key');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['license_category_code', 'source', 'external_id'], 'question_topic_overrides_lookup_unique');
            $table->index('question_topic_key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_topic_overrides');
    }
};
