<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranked_matches', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('matched');
            $table->string('api_version')->default('1.0');
            $table->unsignedSmallInteger('duration_seconds')->default(90);
            $table->unsignedSmallInteger('total_questions')->default(40);
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->string('reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'license_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranked_matches');
    }
};
