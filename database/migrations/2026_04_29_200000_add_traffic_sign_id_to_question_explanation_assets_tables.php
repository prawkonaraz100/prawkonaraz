<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_explanation_assets', function (Blueprint $table) {
            $table->foreignId('traffic_sign_id')->nullable()->after('question_id')->constrained('traffic_signs')->nullOnDelete();
        });

        Schema::table('shared_question_explanation_assets', function (Blueprint $table) {
            $table->foreignId('traffic_sign_id')->nullable()->after('source_scope')->constrained('traffic_signs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('question_explanation_assets', function (Blueprint $table) {
            $table->dropForeign(['traffic_sign_id']);
            $table->dropColumn('traffic_sign_id');
        });

        Schema::table('shared_question_explanation_assets', function (Blueprint $table) {
            $table->dropForeign(['traffic_sign_id']);
            $table->dropColumn('traffic_sign_id');
        });
    }
};
