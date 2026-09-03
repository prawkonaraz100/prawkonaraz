<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('traffic_signs', function (Blueprint $table) {
            $table->string('workflow_status', 32)
                ->default('draft')
                ->after('sort_order');
            $table->foreignId('reviewer_user_id')
                ->nullable()
                ->after('workflow_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')
                ->nullable()
                ->after('reviewer_user_id');
            $table->timestamp('source_checked_at')
                ->nullable()
                ->after('reviewed_at');
            $table->timestamp('freshness_review_due_at')
                ->nullable()
                ->after('source_checked_at');
            $table->text('review_notes')
                ->nullable()
                ->after('editorial_notes');

            $table->index('workflow_status');
            $table->index('source_checked_at');
            $table->index('freshness_review_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('traffic_signs', function (Blueprint $table) {
            $table->dropForeign(['reviewer_user_id']);
            $table->dropIndex(['workflow_status']);
            $table->dropIndex(['source_checked_at']);
            $table->dropIndex(['freshness_review_due_at']);

            $table->dropColumn([
                'workflow_status',
                'reviewer_user_id',
                'reviewed_at',
                'source_checked_at',
                'freshness_review_due_at',
                'review_notes',
            ]);
        });
    }
};
