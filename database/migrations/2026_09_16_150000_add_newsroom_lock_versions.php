<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_articles', function (Blueprint $table): void {
            $table->unsignedBigInteger('lock_version')->default(0);
        });

        Schema::table('content_home_placements', function (Blueprint $table): void {
            $table->unsignedBigInteger('lock_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('content_home_placements', function (Blueprint $table): void {
            $table->dropColumn('lock_version');
        });

        Schema::table('content_articles', function (Blueprint $table): void {
            $table->dropColumn('lock_version');
        });
    }
};
