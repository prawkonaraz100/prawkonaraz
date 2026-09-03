<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_media', function (Blueprint $table): void {
            $table->text('seo_video_description')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('question_media', function (Blueprint $table): void {
            $table->dropColumn('seo_video_description');
        });
    }
};
