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
        Schema::table('questions', function (Blueprint $table) {
            $table->index(
                ['license_category_id', 'is_active', 'delivery_issue', 'question_topic_id'],
                'questions_session_topic_lookup_idx',
            );
            $table->index(
                ['license_category_id', 'is_active', 'delivery_issue', 'difficulty', 'published_at'],
                'questions_session_learning_order_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_session_topic_lookup_idx');
            $table->dropIndex('questions_session_learning_order_idx');
        });
    }
};
