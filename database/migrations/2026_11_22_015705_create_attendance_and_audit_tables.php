<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Settings of the whole system (for example the weekly holidays): key => JSON value
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });

        // The start date and the number of weeks of a semester (about 16 weeks)
        Schema::table('semesters', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('year');
            $table->unsignedTinyInteger('weeks')->default(16)->after('start_date');
        });

        // The teacher decides whether the students can see the attendance of the subject
        Schema::table('subjects', function (Blueprint $table) {
            $table->boolean('attendance_visible')->default(false)->after('credit_hours');
        });

        // One session = one day of one subject
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subject_id', 'date']);
        });

        // A student without a record on a day is blank: he is not counted for that day
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 10); // present | absent | excused
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id']);
        });

        // History of important changes: who, when, what and from which value to which
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50)->index();
            $table->string('description', 500);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('attendance_visible');
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'weeks']);
        });

        Schema::dropIfExists('settings');
    }
};