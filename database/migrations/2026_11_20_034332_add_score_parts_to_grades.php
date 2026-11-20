<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The total stays in "marks". These columns keep the four parts of it.
        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('exam_score', 5, 2)->nullable()->after('chance');      // max 20
            $table->decimal('homework_score', 5, 2)->nullable()->after('exam_score');   // max 10
            $table->decimal('activity_score', 5, 2)->nullable()->after('homework_score'); // max 10
            $table->decimal('final_score', 5, 2)->nullable()->after('activity_score');  // max 60
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['exam_score', 'homework_score', 'activity_score', 'final_score']);
        });
    }
};