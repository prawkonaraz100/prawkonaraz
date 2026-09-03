<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_audio_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('category_code', 20)->nullable();
            $table->string('asset_key')->unique();
            $table->string('content_scope', 60);
            $table->string('audio_type', 60);
            $table->string('locale', 12)->default('pl-PL');
            $table->string('source_text_hash', 64);
            $table->longText('source_text')->nullable();
            $table->string('storage_disk', 50)->default('media_local');
            $table->string('storage_path')->nullable();
            $table->decimal('duration_seconds', 8, 3)->nullable();
            $table->string('encoding_format', 100)->default('audio/mpeg');
            $table->unsignedBigInteger('bytes')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('voice_provider', 50)->default('elevenlabs');
            $table->string('voice_id', 120)->nullable();
            $table->string('voice_name')->nullable();
            $table->string('model_id', 120)->nullable();
            $table->string('generation_version', 80)->default('question-v1');
            $table->string('status', 40)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['external_id', 'content_scope', 'audio_type', 'locale', 'status'], 'question_audio_assets_lookup_index');
            $table->index(['content_scope', 'audio_type', 'status'], 'question_audio_assets_status_index');
            $table->index('source_text_hash', 'question_audio_assets_source_hash_index');
            $table->index(['voice_provider', 'voice_id', 'model_id'], 'question_audio_assets_voice_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_audio_assets');
    }
};
