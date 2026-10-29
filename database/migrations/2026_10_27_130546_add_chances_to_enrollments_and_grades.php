<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enrollments: one row for every chance of a student in a subject
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedTinyInteger('chance')->default(1)->after('semester_id');
            $table->unique(['student_id', 'subject_id', 'chance']);
        });

        // The new unique index must exist before the old one is dropped (MySQL foreign key rule)
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'subject_id', 'semester_id']);
        });

        // Grades: every grade belongs to one enrollment
        Schema::table('grades', function (Blueprint $table) {
            $table->unsignedTinyInteger('chance')->default(1)->after('semester_id');
            $table->foreignId('enrollment_id')->nullable()->after('id')
                ->constrained('enrollments')->cascadeOnDelete();
            $table->unique(['student_id', 'subject_id', 'chance']);
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->unique('enrollment_id');
        });

        // Link the grades that already exist to their enrollments
        DB::statement('
            UPDATE grades
            JOIN enrollments ON enrollments.student_id = grades.student_id
                AND enrollments.subject_id = grades.subject_id
                AND enrollments.chance = grades.chance
            SET grades.enrollment_id = enrollments.id
        ');

        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'subject_id', 'semester_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('academic_status', ['active', 'graduated', 'failed'])
                ->default('active')->after('class_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('academic_status');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->unique(['student_id', 'subject_id', 'semester_id']);
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
            $table->dropUnique(['enrollment_id']);
            $table->dropUnique(['student_id', 'subject_id', 'chance']);
            $table->dropColumn(['enrollment_id', 'chance']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_id', 'subject_id', 'semester_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'subject_id', 'chance']);
            $table->dropColumn('chance');
        });
    }
};