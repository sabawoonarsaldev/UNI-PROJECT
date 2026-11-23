<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_path')->nullable();

            // For everybody
            $table->string('father_name', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();

            // For teachers
            $table->string('academic_rank', 20)->nullable();
            $table->string('education_degree', 20)->nullable();
            $table->string('specialization', 150)->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'photo_path', 'father_name', 'date_of_birth', 'gender', 'phone', 'address',
                'academic_rank', 'education_degree', 'specialization', 'experience_years',
            ]);
        });
    }
};