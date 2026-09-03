<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_topics', function (Blueprint $table): void {
            $table->string('hero_image_path')->nullable()->after('description');
            $table->string('hero_image_alt')->nullable()->after('hero_image_path');
            $table->string('hero_image_position', 80)->nullable()->after('hero_image_alt');
        });
    }

    public function down(): void
    {
        Schema::table('question_topics', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_image_path',
                'hero_image_alt',
                'hero_image_position',
            ]);
        });
    }
};

