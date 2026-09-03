<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('kind', 50);
            $table->string('source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(
                ['license_category_id', 'is_active', 'is_public'],
                'question_collections_visibility_idx',
            );
        });

        Schema::create('question_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_collection_id')->constrained()->cascadeOnDelete();
            $table->string('source_id', 100)->nullable();
            $table->string('code', 100);
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('expected_questions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['question_collection_id', 'code'], 'question_modules_collection_code_unique');
            $table->unique(['question_collection_id', 'slug'], 'question_modules_collection_slug_unique');
            $table->unique(['question_collection_id', 'source_id'], 'question_modules_collection_source_unique');
            $table->index(['question_collection_id', 'is_active', 'sort_order'], 'question_modules_listing_idx');
        });

        Schema::create('question_module_question', function (Blueprint $table): void {
            $table->foreignId('question_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->primary(['question_module_id', 'question_id'], 'question_module_question_primary');
            $table->unique(['question_module_id', 'position'], 'question_module_question_position_unique');
            $table->index('question_id', 'question_module_question_question_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_module_question');
        Schema::dropIfExists('question_modules');
        Schema::dropIfExists('question_collections');
    }
};
