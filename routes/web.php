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
# Project Management 
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectCommentController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\SupervisionController;
use App\Http\Controllers\Teacher\ProjectController as TeacherProjectController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceSettingsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\MyProfileController;
use App\Http\Controllers\UserPhotoController;


Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('my-profile'),
            'student' => redirect()->route('my-profile'),
        };
    })->name('dashboard');

    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('messages/start', [MessageController::class, 'start'])->name('messages.start');
    Route::get('messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('messages/{user}', [MessageController::class, 'store'])->name('messages.store');
// The profile page: the first page of teachers and students after the login
    Route::get('my-profile', [MyProfileController::class, 'show'])->name('my-profile');
    Route::put('my-profile', [MyProfileController::class, 'update'])->name('my-profile.update');
    Route::get('users/{user}/photo', [UserPhotoController::class, 'show'])->name('users.photo');

    // Project repository: every logged-in user can search the approved projects
    Route::get('repository', [RepositoryController::class, 'index'])->name('repository.index');
    Route::get('repository/{project}', [RepositoryController::class, 'show'])->name('repository.show');

// Project workspace: student, supervisor and admin (ProjectPolicy checks who may do what)
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('projects/{project}/submit', [ProjectController::class, 'submit'])->name('projects.submit');
    Route::post('projects/{project}/supervisor-approve', [ProjectController::class, 'supervisorApprove'])->name('projects.supervisor-approve');
    Route::post('projects/{project}/request-revision', [ProjectController::class, 'requestRevision'])->name('projects.request-revision');
    Route::post('projects/{project}/final-approve', [ProjectController::class, 'finalApprove'])->name('projects.final-approve');
    Route::post('projects/{project}/return', [ProjectController::class, 'returnToStudent'])->name('projects.return');
    Route::post('projects/{project}/files', [ProjectFileController::class, 'store'])->name('projects.files.store');
    Route::get('project-files/{file}/download', [ProjectFileController::class, 'download'])->name('projects.files.download');
    Route::post('projects/{project}/comments', [ProjectCommentController::class, 'store'])->name('projects.comments.store');


    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
        Route::resource('faculties', FacultyController::class)->except('show');
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('users', UserController::class);
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
        Route::post('subjects/{subject}/grades/publish', [AdminGradeController::class, 'publish'])->name('grades.publish');
        Route::post('subjects/{subject}/grades/unpublish', [AdminGradeController::class, 'unpublish'])->name('grades.unpublish');
        Route::post('subjects/{subject}/grades/return', [AdminGradeController::class, 'returnToTeacher'])->name('grades.return');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        // Attendance: faculty -> department -> class -> semester -> subject
        Route::get('attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/sessions/{attendanceSession}', [AdminAttendanceController::class, 'session'])->name('attendance.session');
        Route::post('attendance/sessions/{attendanceSession}/lock', [AdminAttendanceController::class, 'lock'])->name('attendance.lock');
        Route::post('attendance/sessions/{attendanceSession}/unlock', [AdminAttendanceController::class, 'unlock'])->name('attendance.unlock');
        Route::get('attendance/{subject}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
        Route::post('attendance/{subject}/lock-all', [AdminAttendanceController::class, 'lockAll'])->name('attendance.lock-all');
        Route::post('attendance/{subject}/unlock-all', [AdminAttendanceController::class, 'unlockAll'])->name('attendance.unlock-all');

        Route::get('attendance-settings', [AttendanceSettingsController::class, 'edit'])->name('attendance-settings.edit');
        Route::put('attendance-settings', [AttendanceSettingsController::class, 'update'])->name('attendance-settings.update');
        Route::put('attendance-settings/semesters/{semester}', [AttendanceSettingsController::class, 'updateSemester'])->name('attendance-settings.semester');

        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
        Route::resource('semester-types', SemesterTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('classes/{classGroup}/process-results', [ClassGroupController::class, 'processResults'])->name('classes.process-results');
        Route::post('classes/{classGroup}/publish-grades', [ClassGroupController::class, 'publishGrades'])->name('classes.publish-grades');
        Route::get('supervision', [SupervisionController::class, 'index'])->name('supervision.index');
        Route::put('supervision/{student}', [SupervisionController::class, 'update'])->name('supervision.update');
        Route::get('projects', [AdminProjectController::class, 'index'])->name('projects.index');
        Route::delete('projects/{project}', [AdminProjectController::class, 'destroy'])->name('projects.destroy');
    });

    Route::middleware(['role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/dashboard', [TeacherDashboard::class, 'index'])->name('dashboard');
        Route::get('subjects', [TeacherSubjectController::class, 'index'])->name('subjects.index');
        Route::get('subjects/{subject}', [TeacherSubjectController::class, 'show'])->name('subjects.show');
        Route::get('subjects/{subject}/grades', [TeacherGradeController::class, 'edit'])->name('grades.edit');
        Route::put('subjects/{subject}/grades', [TeacherGradeController::class, 'update'])->name('grades.update');
        Route::post('subjects/{subject}/grades/submit', [TeacherGradeController::class, 'submit'])->name('grades.submit');
        Route::get('schedule', [TeacherScheduleController::class, 'index'])->name('schedule');
        Route::get('attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/sessions/{attendanceSession}/edit', [TeacherAttendanceController::class, 'edit'])->name('attendance.edit');
        Route::put('attendance/sessions/{attendanceSession}', [TeacherAttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('attendance/sessions/{attendanceSession}', [TeacherAttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::get('attendance/{subject}', [TeacherAttendanceController::class, 'show'])->name('attendance.show');
        Route::put('attendance/{subject}/visibility', [TeacherAttendanceController::class, 'visibility'])->name('attendance.visibility');
        Route::get('attendance/{subject}/create', [TeacherAttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance/{subject}', [TeacherAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('projects', [TeacherProjectController::class, 'index'])->name('projects.index');
    });

    Route::middleware(['role:student'])->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentDashboard::class, 'index'])->name('dashboard');
        Route::get('subjects', [StudentSubjectController::class, 'index'])->name('subjects');
        Route::get('grades', [StudentGradeController::class, 'index'])->name('grades');
        Route::get('schedule', [StudentScheduleController::class, 'index'])->name('schedule');
        Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance');
        Route::get('project', [ProjectController::class, 'mine'])->name('project');
        Route::post('project', [ProjectController::class, 'store'])->name('project.store');
        });
});     

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';