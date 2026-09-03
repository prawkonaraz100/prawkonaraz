<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_explanation_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 50);
            $table->string('disk', 50)->default('public');
            $table->string('file_path')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['question_id', 'kind']);
            $table->index(['question_id', 'kind', 'is_active']);
            $table->index(['question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_explanation_assets');
    }
};
