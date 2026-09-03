<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->boolean('auto_remove_incorrect_questions_on_correct')
                ->default(false)
                ->after('visual_explanations_mode');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn('auto_remove_incorrect_questions_on_correct');
        });
    }
};
