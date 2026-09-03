<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            if (Schema::hasColumn('questions', 'friendly_explanation')) {
                $table->dropColumn('friendly_explanation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            if (! Schema::hasColumn('questions', 'friendly_explanation')) {
                $table->longText('friendly_explanation')->nullable()->after('explanation');
            }
        });
    }
};
