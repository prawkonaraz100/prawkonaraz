<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ip_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('path', 255)->nullable();
            $table->string('session_id', 255)->nullable();
            $table->string('request_id', 120)->nullable();
            $table->unsignedInteger('hit_count')->default(1);
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();

            $table->index(['user_id', 'last_seen_at']);
            $table->index(['source', 'last_seen_at']);
            $table->index(['ip_address', 'last_seen_at']);
            $table->index(['session_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ip_histories');
    }
};
