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
        Schema::create('traffic_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_author_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('traffic_sign_category_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('code', 20)->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('intro_definition')->nullable();
            $table->text('meaning')->nullable();
            $table->text('placement')->nullable();
            $table->text('driver_behavior')->nullable();
            $table->text('legal_summary')->nullable();
            $table->string('legal_reference_label')->nullable();
            $table->string('legal_reference_url', 2048)->nullable();
            $table->text('fine_summary')->nullable();
            $table->text('common_mistakes')->nullable();
            $table->text('editorial_notes')->nullable();
            $table->text('source_notes')->nullable();
            $table->json('faq_items')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('image_path', 2048)->nullable();
            $table->string('og_image_path', 2048)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['traffic_sign_category_id', 'is_published', 'sort_order']);
            $table->index(['content_author_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_signs');
    }
};
