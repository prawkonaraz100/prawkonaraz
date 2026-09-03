<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ip_histories', function (Blueprint $table): void {
            $table->string('country_code', 8)->nullable()->after('ip_address');
            $table->string('country_name', 120)->nullable()->after('country_code');
            $table->string('city_name', 120)->nullable()->after('country_name');

            $table->index(['country_code', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::table('user_ip_histories', function (Blueprint $table): void {
            $table->dropIndex(['country_code', 'last_seen_at']);
            $table->dropColumn(['country_code', 'country_name', 'city_name']);
        });
    }
};
