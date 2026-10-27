<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboard;
use App\Http\Controllers\Student\DashboardController as StudentDashboard;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\ClassScheduleController;
#teacher routes
use App\Http\Controllers\Teacher\SubjectController as TeacherSubjectController;
use App\Http\Controllers\Teacher\GradeController as TeacherGradeController;
use App\Http\Controllers\Teacher\ScheduleController as TeacherScheduleController;
# student
use App\Http\Controllers\Student\SubjectController as StudentSubjectController;
use App\Http\Controllers\Student\GradeController as StudentGradeController;
use App\Http\Controllers\Student\ScheduleController as StudentScheduleController;
#####
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
####
use App\Http\Controllers\Admin\GradeController as AdminGradeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ConfigurationController;
use App\Http\Controllers\Admin\SemesterTypeController;
use App\Http\Controllers\Admin\ClassGroupController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'student' => redirect()->route('student.dashboard'),
        };
    })->name('dashboard');

    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('messages/start', [MessageController::class, 'start'])->name('messages.start');
    Route::get('messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('messages/{user}', [MessageController::class, 'store'])->name('messages.store');

    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
        Route::resource('faculties', FacultyController::class)->except('show');
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::get('classes', [ClassGroupController::class, 'index'])->name('classes.index');
        Route::get('classes/create', [ClassGroupController::class, 'create'])->name('classes.create');
        Route::post('classes', [ClassGroupController::class, 'store'])->name('classes.store');
        Route::get('classes/{classGroup}', [ClassGroupController::class, 'show'])->name('classes.show');
        Route::delete('classes/{classGroup}', [ClassGroupController::class, 'destroy'])->name('classes.destroy');
        Route::post('classes/{classGroup}/students', [ClassGroupController::class, 'addStudents'])->name('classes.students.add');
        Route::delete('classes/{classGroup}/students/{student}', [ClassGroupController::class, 'removeStudent'])->name('classes.students.remove');
        Route::post('classes/{classGroup}/complete-semester', [ClassGroupController::class, 'completeSemester'])->name('classes.complete-semester');
        Route::get('classes/{classGroup}/results', [ClassGroupController::class, 'results'])->name('classes.results');
        Route::resource('subjects', SubjectController::class)->except('show');
        Route::get('enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
        Route::get('subjects/{subject}/enrollments', [EnrollmentController::class, 'manage'])->name('enrollments.manage');
        Route::post('subjects/{subject}/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::post('subjects/{subject}/enrollments/all', [EnrollmentController::class, 'enrollAll'])->name('enrollments.enroll-all');
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
        Route::resource('schedules', ClassScheduleController::class)->except('show');
        Route::resource('announcements', AdminAnnouncementController::class)->except('show');
        Route::get('grades', [AdminGradeController::class, 'index'])->name('grades.index');
        Route::get('subjects/{subject}/grades', [AdminGradeController::class, 'show'])->name('grades.show');
        Route::put('subjects/{subject}/grades', [AdminGradeController::class, 'update'])->name('grades.update');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
        Route::resource('semester-types', SemesterTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        });

    Route::middleware(['role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/dashboard', [TeacherDashboard::class, 'index'])->name('dashboard');
        Route::get('subjects', [TeacherSubjectController::class, 'index'])->name('subjects.index');
        Route::get('subjects/{subject}', [TeacherSubjectController::class, 'show'])->name('subjects.show');
        Route::get('subjects/{subject}/grades', [TeacherGradeController::class, 'edit'])->name('grades.edit');
        Route::put('subjects/{subject}/grades', [TeacherGradeController::class, 'update'])->name('grades.update');
        Route::get('schedule', [TeacherScheduleController::class, 'index'])->name('schedule');
    });

    Route::middleware(['role:student'])->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentDashboard::class, 'index'])->name('dashboard');
        Route::get('subjects', [StudentSubjectController::class, 'index'])->name('subjects');
        Route::get('grades', [StudentGradeController::class, 'index'])->name('grades');
        Route::get('schedule', [StudentScheduleController::class, 'index'])->name('schedule');
    
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';