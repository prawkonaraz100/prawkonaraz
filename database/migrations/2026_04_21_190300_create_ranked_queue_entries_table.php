<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranked_queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ranked_match_id')->nullable()->constrained('ranked_matches')->nullOnDelete();
            $table->string('status')->default('queued');
            $table->timestamp('joined_at');
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['license_category_id', 'status', 'joined_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranked_queue_entries');
    }
};
