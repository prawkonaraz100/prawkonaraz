<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_videos', function (Blueprint $table): void {
            $table->string('kind', 20)->default('video')->after('title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('homepage_videos', function (Blueprint $table): void {
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });
    }
};
