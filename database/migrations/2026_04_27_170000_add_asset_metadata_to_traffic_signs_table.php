<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('traffic_signs', function (Blueprint $table) {
            $table->string('image_alt')->nullable()->after('image_path');
            $table->unsignedInteger('image_width')->nullable()->after('image_alt');
            $table->unsignedInteger('image_height')->nullable()->after('image_width');
            $table->string('og_image_alt')->nullable()->after('og_image_path');
            $table->unsignedInteger('og_image_width')->nullable()->after('og_image_alt');
            $table->unsignedInteger('og_image_height')->nullable()->after('og_image_width');
        });
    }

    public function down(): void
    {
        Schema::table('traffic_signs', function (Blueprint $table) {
            $table->dropColumn([
                'image_alt',
                'image_width',
                'image_height',
                'og_image_alt',
                'og_image_width',
                'og_image_height',
            ]);
        });
    }
};
