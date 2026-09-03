<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_public_explanations', function (Blueprint $table): void {
            $table->json('related_questions')->nullable()->after('common_mistakes');
        });
    }

    public function down(): void
    {
        Schema::table('question_public_explanations', function (Blueprint $table): void {
            $table->dropColumn('related_questions');
        });
    }
};
