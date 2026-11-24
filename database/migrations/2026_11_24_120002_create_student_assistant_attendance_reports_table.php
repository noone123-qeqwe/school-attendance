<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_assistant_attendance_reports')) {
            $this->finishExistingTable();
            return;
        }

        Schema::create('student_assistant_attendance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained(indexName: 'assistant_reports_session_fk')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->string('suggested_status', 12);
            $table->string('review_status', 12)->default('pending');
            $table->string('reason', 250)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id'], 'assistant_reports_unique_student_session');
            $table->index(['attendance_session_id', 'review_status'], 'assistant_reports_session_status');
            $table->index(['submitted_by', 'attendance_session_id'], 'assistant_reports_submitter_session');
        });
    }

    private function finishExistingTable(): void
    {
        // MySQL-compatible databases can retain the table after a later DDL step fails.
        $name = 'student_assistant_attendance_reports';
        $foreignKeys = collect(Schema::getForeignKeys($name));
        foreach ([
            'attendance_session_id' => ['attendance_sessions', 'cascade', 'assistant_reports_session_fk'],
            'student_id' => ['users', 'cascade', 'assistant_reports_student_fk'],
            'submitted_by' => ['users', 'set null', 'assistant_reports_submitter_fk'],
            'reviewed_by' => ['users', 'set null', 'assistant_reports_reviewer_fk'],
            'attendance_id' => ['attendances', 'set null', 'assistant_reports_attendance_fk'],
        ] as $column => [$target, $onDelete, $constraint]) {
            if ($foreignKeys->contains(fn (array $key) => $key['columns'] === [$column]
                && $key['foreign_table'] === $target
                && $key['foreign_columns'] === ['id']
                && $key['on_delete'] === $onDelete)) {
                continue;
            }

            Schema::table($name, fn (Blueprint $table) => $table->foreign($column, $constraint)
                ->references('id')->on($target)->onDelete($onDelete));
        }

        foreach ([
            'assistant_reports_unique_student_session' => [['attendance_session_id', 'student_id'], true],
            'assistant_reports_session_status' => [['attendance_session_id', 'review_status'], false],
            'assistant_reports_submitter_session' => [['submitted_by', 'attendance_session_id'], false],
        ] as $index => [$columns, $unique]) {
            if (!Schema::hasIndex($name, $index)) {
                Schema::table($name, fn (Blueprint $table) => $unique
                    ? $table->unique($columns, $index)
                    : $table->index($columns, $index));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_assistant_attendance_reports');
    }
};
