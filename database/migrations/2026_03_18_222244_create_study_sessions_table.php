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
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode', 20)->default('exam');
            $table->string('status', 20)->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('correct_answers_count')->default(0);
            $table->unsignedSmallInteger('total_questions_count')->default(0);
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['license_category_id', 'mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
