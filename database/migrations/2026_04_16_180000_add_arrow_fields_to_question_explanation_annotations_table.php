<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_explanation_annotations', function (Blueprint $table): void {
            $table->decimal('arrow_length_percent', 6, 2)->nullable()->after('height_percent');
            $table->unsignedSmallInteger('arrow_angle_degrees')->nullable()->after('arrow_length_percent');
            $table->decimal('arrow_stroke_percent', 6, 2)->nullable()->after('arrow_angle_degrees');
            $table->decimal('arrow_head_percent', 6, 2)->nullable()->after('arrow_stroke_percent');
        });
    }

    public function down(): void
    {
        Schema::table('question_explanation_annotations', function (Blueprint $table): void {
            $table->dropColumn([
                'arrow_length_percent',
                'arrow_angle_degrees',
                'arrow_stroke_percent',
                'arrow_head_percent',
            ]);
        });
    }
};
