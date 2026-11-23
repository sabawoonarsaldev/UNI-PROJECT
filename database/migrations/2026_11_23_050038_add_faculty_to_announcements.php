<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // faculty empty = every faculty; faculty without department = every department of that faculty
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('faculty_id')->nullable()->after('target_role')
                ->constrained('faculties')->nullOnDelete();
        });

        // The announcements of one department belong to the faculty of that department
        DB::statement('
            UPDATE announcements
            JOIN departments ON departments.id = announcements.department_id
            SET announcements.faculty_id = departments.faculty_id
        ');
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropColumn('faculty_id');
        });
    }
};