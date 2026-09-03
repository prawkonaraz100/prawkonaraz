<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_public_explanations', function (Blueprint $table): void {
            $table->text('dont_confuse_with')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('question_public_explanations', function (Blueprint $table): void {
            $table->dropColumn('dont_confuse_with');
        });
    }
};
