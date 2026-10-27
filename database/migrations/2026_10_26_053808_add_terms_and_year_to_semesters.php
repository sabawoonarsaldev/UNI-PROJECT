<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semester_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();
        });

        // The two default terms, the admin can change them in Configuration
        DB::table('semester_types')->insert([
            ['name' => 'Spring', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Fall', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('semesters', function (Blueprint $table) {
            $table->foreignId('semester_type_id')->nullable()->after('department_id')
                ->constrained('semester_types')->restrictOnDelete();
            $table->unsignedSmallInteger('year')->nullable()->after('semester_type_id');

            // The same term and year cannot exist twice in one department
            $table->unique(['department_id', 'semester_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropUnique(['department_id', 'semester_type_id', 'year']);
            $table->dropForeign(['semester_type_id']);
            $table->dropColumn(['semester_type_id', 'year']);
        });

        Schema::dropIfExists('semester_types');
    }
};