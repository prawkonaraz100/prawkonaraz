<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_explanation_drafts', function (Blueprint $table) {
            $table->unsignedInteger('applied_question_count')->default(0)->after('local_existing_explanation_count');
            $table->unsignedInteger('skipped_existing_question_count')->default(0)->after('applied_question_count');
            $table->timestamp('last_applied_at')->nullable()->after('staging_issue');
            $table->json('last_apply_report')->nullable()->after('source_payload');
        });
    }

    public function down(): void
    {
        Schema::table('question_explanation_drafts', function (Blueprint $table) {
            $table->dropColumn([
                'applied_question_count',
                'skipped_existing_question_count',
                'last_applied_at',
                'last_apply_report',
            ]);
        });
    }
};
