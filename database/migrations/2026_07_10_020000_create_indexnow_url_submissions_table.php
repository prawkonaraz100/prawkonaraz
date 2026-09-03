<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexnow_url_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('url', 2048);
            $table->string('url_hash', 64)->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->string('source', 80)->nullable()->index();
            $table->string('event_type', 40)->default('updated');
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('last_enqueued_at')->nullable();
            $table->timestamp('last_submitted_at')->nullable();
            $table->unsignedInteger('enqueued_count')->default(1);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->string('last_response_reason', 120)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indexnow_url_submissions');
    }
};
