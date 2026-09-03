<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->foreignId('target_category_id')->nullable()->constrained('license_categories')->nullOnDelete();
            $table->date('exam_date')->nullable();
            $table->unsignedSmallInteger('study_streak')->default(0);
            $table->date('last_study_date')->nullable();
            $table->string('tier')->default('free');
            $table->string('onboarding_step')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
