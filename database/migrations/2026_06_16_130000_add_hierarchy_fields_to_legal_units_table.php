<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_units', function (Blueprint $table): void {
            $table->foreignId('parent_legal_unit_id')
                ->nullable()
                ->after('legal_act_id')
                ->constrained('legal_units')
                ->nullOnDelete();
            $table->string('canonical_path', 120)
                ->nullable()
                ->after('label');
            $table->date('effective_from')
                ->nullable()
                ->after('source_url');

            $table->unique(['legal_act_id', 'canonical_path'], 'legal_units_act_canonical_path_unique');
            $table->index(['parent_legal_unit_id', 'status'], 'legal_units_parent_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('legal_units', function (Blueprint $table): void {
            $table->dropUnique('legal_units_act_canonical_path_unique');
            $table->dropIndex('legal_units_parent_status_index');
            $table->dropConstrainedForeignId('parent_legal_unit_id');
            $table->dropColumn(['canonical_path', 'effective_from']);
        });
    }
};
