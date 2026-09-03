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
        Schema::table('question_media', function (Blueprint $table) {
            $table->unsignedBigInteger('bytes')->nullable()->after('mime_type');
            $table->string('variant', 20)->default('full')->after('height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_media', function (Blueprint $table) {
            $table->dropColumn(['bytes', 'variant']);
        });
    }
};
