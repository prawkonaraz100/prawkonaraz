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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->text('prompt');
            $table->longText('explanation')->nullable();
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c')->nullable();
            $table->string('correct_answer', 1);
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->unsignedTinyInteger('points')->default(1);
            $table->string('question_type', 32)->default('single_choice');
            $table->boolean('is_active')->default(true);
            $table->string('source')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['license_category_id', 'external_id']);
            $table->index(['license_category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
