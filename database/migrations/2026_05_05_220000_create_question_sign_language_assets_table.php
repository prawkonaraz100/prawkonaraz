<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_sign_language_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id');
            $table->string('asset_role', 32);
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('source_filename')->nullable();
            $table->string('source_path')->nullable();
            $table->string('mime_type', 100)->default('video/mp4');
            $table->unsignedBigInteger('bytes')->nullable();
            $table->decimal('duration_seconds', 8, 3)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('variant', 50)->default('standard');
            $table->string('processing_profile', 80)->nullable();
            $table->string('processing_status', 40)->default('ready');
            $table->boolean('is_active')->default(true);
            $table->boolean('review_required')->default(false);
            $table->string('checksum_sha256', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['external_id', 'asset_role', 'variant'], 'question_sign_language_assets_unique_role_variant');
            $table->index(['external_id', 'asset_role', 'is_active'], 'question_sign_language_assets_lookup_index');
            $table->index(['asset_role', 'processing_status', 'is_active'], 'question_sign_language_assets_status_index');
            $table->index(['review_required', 'is_active'], 'question_sign_language_assets_review_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_sign_language_assets');
    }
};
