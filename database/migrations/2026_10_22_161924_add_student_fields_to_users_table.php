<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('roll_number')->nullable()->unique()->after('department_id');
        $table->foreignId('current_semester_id')->nullable()->constrained('semesters')->onDelete('set null')->after('roll_number');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['current_semester_id']);
        $table->dropColumn(['roll_number', 'current_semester_id']);
    });
}
};
