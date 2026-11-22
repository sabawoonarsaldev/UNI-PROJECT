<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            // draft -> submitted (by the teacher) -> published (by the admin)
            $table->string('status', 12)->default('draft')->after('grade_letter');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('published_at')->nullable()->after('submitted_at');
            $table->foreignId('published_by')->nullable()->after('published_at')
                ->constrained('users')->nullOnDelete();

            $table->index(['subject_id', 'status']);
        });

        // The grades that exist now were already visible to the students, so they stay published
        DB::table('grades')->update(['status' => 'published', 'published_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['published_by']);
            $table->dropIndex(['subject_id', 'status']);
            $table->dropColumn(['status', 'submitted_at', 'published_at', 'published_by']);
        });
    }
};