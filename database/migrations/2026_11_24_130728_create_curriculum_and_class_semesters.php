<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The curriculum: the subjects of a department for every semester number
        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('semester_number');
            $table->string('name');
            $table->string('code', 50);
            $table->unsignedTinyInteger('credit_hours')->default(3);
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['department_id', 'code']);
            $table->index(['department_id', 'semester_number']);
        });

        // Some departments study 8 semesters, some 10 or more
        Schema::table('class_groups', function (Blueprint $table) {
            $table->unsignedTinyInteger('total_semesters')->default(8)->after('entry_year');
        });

        // A subject of a class is a copy of a curriculum subject
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('curriculum_subject_id')->nullable()->after('semester_id')
                ->constrained('curriculum_subjects')->nullOnDelete();

            // The same subject is taught to many classes, so the code is unique only inside one semester
            $table->unique(['semester_id', 'code']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        // The subjects that already exist become part of the curriculum, so nothing is lost
        DB::statement('
            INSERT INTO curriculum_subjects (department_id, semester_number, name, code, credit_hours, teacher_id, created_at, updated_at)
            SELECT subjects.department_id, semesters.number, subjects.name, subjects.code, subjects.credit_hours, subjects.teacher_id, NOW(), NOW()
            FROM subjects
            JOIN semesters ON semesters.id = subjects.semester_id
            WHERE semesters.number IS NOT NULL
        ');

        DB::statement('
            UPDATE subjects
            JOIN curriculum_subjects
              ON curriculum_subjects.department_id = subjects.department_id
             AND curriculum_subjects.code = subjects.code
            SET subjects.curriculum_subject_id = curriculum_subjects.id
        ');
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['curriculum_subject_id']);
            $table->dropUnique(['semester_id', 'code']);
            $table->dropColumn('curriculum_subject_id');
        });

        Schema::table('class_groups', function (Blueprint $table) {
            $table->dropColumn('total_semesters');
        });

        Schema::dropIfExists('curriculum_subjects');
    }
};