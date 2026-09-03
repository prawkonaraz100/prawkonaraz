<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_learning_order_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_topic_id')->constrained('question_topics')->cascadeOnDelete();
            $table->string('question_scope', 20)->default('all');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->string('active_marker', 20)->nullable();
            $table->timestamps();

            $table->index(
                ['license_category_id', 'question_topic_id', 'question_scope', 'status'],
                'qlos_lookup_idx',
            );
            $table->unique(
                ['license_category_id', 'question_topic_id', 'question_scope', 'active_marker'],
                'qlos_one_active_unique',
            );
            $table->index('updated_by', 'qlos_updated_by_idx');
            $table->index('published_at', 'qlos_published_at_idx');
        });

        Schema::create('question_learning_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_learning_order_set_id')
                ->constrained('question_learning_order_sets')
                ->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(
                ['question_learning_order_set_id', 'question_id'],
                'qloi_set_question_unique',
            );
            $table->unique(
                ['question_learning_order_set_id', 'position'],
                'qloi_set_position_unique',
            );
            $table->index('question_id', 'qloi_question_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_learning_order_items');
        Schema::dropIfExists('question_learning_order_sets');
    }
};
