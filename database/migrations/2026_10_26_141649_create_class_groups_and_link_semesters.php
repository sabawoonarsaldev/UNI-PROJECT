<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_type_id')->constrained('semester_types')->restrictOnDelete();
            $table->unsignedSmallInteger('entry_year');
            $table->unsignedTinyInteger('current_semester_number')->default(1);
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->timestamps();

            $table->unique(['department_id', 'semester_type_id', 'entry_year']);
        });

        // MySQL uses the unique index below as the index of the department foreign key.
        // A normal index must exist first, otherwise the unique index cannot be dropped.
        Schema::table('semesters', function (Blueprint $table) {
            $table->index('department_id');
        });

        // Two classes can have the same term and year (with different semester numbers)
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropUnique(['department_id', 'semester_type_id', 'year']);
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->foreignId('class_group_id')->nullable()->after('department_id')
                ->constrained('class_groups')->cascadeOnDelete();
            $table->unsignedTinyInteger('number')->nullable()->after('class_group_id');

            $table->unique(['class_group_id', 'number']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('class_group_id')->nullable()->after('current_semester_id')
                ->constrained('class_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['class_group_id']);
            $table->dropColumn('class_group_id');
        });

        // The foreign key is dropped first, so the unique index is free to be dropped
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropForeign(['class_group_id']);
            $table->dropUnique(['class_group_id', 'number']);
            $table->dropColumn(['class_group_id', 'number']);
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->unique(['department_id', 'semester_type_id', 'year']);
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropIndex(['department_id']);
        });

        Schema::dropIfExists('class_groups');
    }
};