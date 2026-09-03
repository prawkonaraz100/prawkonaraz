<?php

use App\Models\UserProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('preferred_learning_track', 32)
                ->default(UserProfile::LEARNING_TRACK_UNDECIDED)
                ->after('target_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn('preferred_learning_track');
        });
    }
};
