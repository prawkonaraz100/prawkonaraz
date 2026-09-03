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
        Schema::table('monitoring_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('database_volume_total_bytes')->nullable()->after('database_size_bytes');
            $table->unsignedBigInteger('database_volume_free_bytes')->nullable()->after('database_volume_total_bytes');
            $table->unsignedInteger('database_latency_ms')->nullable()->after('database_volume_free_bytes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'database_volume_total_bytes',
                'database_volume_free_bytes',
                'database_latency_ms',
            ]);
        });
    }
};
