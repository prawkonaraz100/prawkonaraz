<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_explanation_annotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('target_kind');
            $table->unsignedInteger('frame_time_seconds')->nullable();
            $table->string('annotation_type');
            $table->decimal('x_percent', 6, 2);
            $table->decimal('y_percent', 6, 2);
            $table->decimal('width_percent', 6, 2)->nullable();
            $table->decimal('height_percent', 6, 2)->nullable();
            $table->string('label')->nullable();
            $table->string('tone')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['question_id', 'target_kind']);
            $table->index(['question_id', 'target_kind', 'is_active']);
            $table->index(['question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_explanation_annotations');
    }
};
