<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->addForeignKeyIfMissing(
            table: 'question_media',
            constraint: 'question_media_question_id_foreign',
            column: 'question_id',
            references: 'questions',
        );

        $this->addForeignKeyIfMissing(
            table: 'study_session_answers',
            constraint: 'study_session_answers_study_session_id_foreign',
            column: 'study_session_id',
            references: 'study_sessions',
        );

        $this->addForeignKeyIfMissing(
            table: 'study_session_answers',
            constraint: 'study_session_answers_question_id_foreign',
            column: 'question_id',
            references: 'questions',
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->dropForeignKeyIfExists('study_session_answers', 'study_session_answers_question_id_foreign');
        $this->dropForeignKeyIfExists('study_session_answers', 'study_session_answers_study_session_id_foreign');
        $this->dropForeignKeyIfExists('question_media', 'question_media_question_id_foreign');
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $constraint,
        string $column,
        string $references
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasTable($references) || $this->foreignKeyExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($constraint, $column, $references): void {
            $table->foreign($column, $constraint)->references('id')->on($references)->cascadeOnDelete();
        });
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        if (! Schema::hasTable($table) || ! $this->foreignKeyExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($constraint): void {
            $table->dropForeign($constraint);
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        return match ($driver) {
            'pgsql' => DB::table('information_schema.table_constraints')
                ->where('table_schema', $this->postgresSchema())
                ->where('table_name', $table)
                ->where('constraint_name', $constraint)
                ->where('constraint_type', 'FOREIGN KEY')
                ->exists(),
            'mysql' => DB::table('information_schema.table_constraints')
                ->where('table_schema', $connection->getDatabaseName())
                ->where('table_name', $table)
                ->where('constraint_name', $constraint)
                ->where('constraint_type', 'FOREIGN KEY')
                ->exists(),
            default => false,
        };
    }

    private function postgresSchema(): string
    {
        $schema = Schema::getConnection()->getConfig('schema');

        if (is_string($schema) && $schema !== '') {
            $first = explode(',', $schema)[0] ?? 'public';

            return trim($first);
        }

        return 'public';
    }
};
