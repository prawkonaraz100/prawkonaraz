<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table
                ->string('visual_explanations_mode', 32)
                ->default('after_incorrect')
                ->after('visual_explanations_enabled');
        });

        DB::table('user_profiles')
            ->where('visual_explanations_enabled', false)
            ->update([
                'visual_explanations_mode' => 'off',
            ]);
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('visual_explanations_mode');
        });
    }
};
