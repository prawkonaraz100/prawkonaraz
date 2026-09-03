<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_topic_category_heroes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_topic_id')->constrained('question_topics')->cascadeOnDelete();
            $table->string('hero_image_path');
            $table->string('hero_image_alt')->nullable();
            $table->string('hero_image_position', 80)->nullable();
            $table->text('admin_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['license_category_id', 'question_topic_id'],
                'topic_category_heroes_unique_category_topic',
            );
            $table->index('question_topic_id', 'topic_category_heroes_topic_idx');
            $table->index('is_active', 'topic_category_heroes_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_topic_category_heroes');
    }
};
