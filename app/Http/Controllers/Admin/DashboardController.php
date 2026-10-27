<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'Faculties' => Faculty::count(),
            'Departments' => Department::count(),
            'Teachers' => User::where('role', 'teacher')->count(),
            'Students' => User::where('role', 'student')->count(),
            'Subjects' => Subject::count(),
            'Enrollments' => Enrollment::count(),
        ];

        // Things the admin still needs to fix
        $warnings = [
            'subjects_without_teacher' => Subject::whereNull('teacher_id')->count(),
            'subjects_without_schedule' => Subject::whereDoesntHave('schedules')->count(),
            'students_without_class' => User::where('role', 'student')->whereNull('class_group_id')->count(),  
                  ];

        $departments = Department::with('faculty')
            ->withCount([
                'users as teachers_count' => fn ($query) => $query->where('role', 'teacher'),
                'users as students_count' => fn ($query) => $query->where('role', 'student'),
                'subjects',
            ])
            ->orderBy('name')
            ->get();

        // How many grades of each letter exist (letters without grades count as 0)
        $counts = Grade::whereNotNull('grade_letter')
            ->select('grade_letter', DB::raw('COUNT(*) as total'))
            ->groupBy('grade_letter')
            ->pluck('total', 'grade_letter');

        $gradeDistribution = collect(['A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'F'])
            ->mapWithKeys(fn ($letter) => [$letter => (int) ($counts[$letter] ?? 0)]);

        $recentUsers = User::with('department')->latest()->take(5)->get();
        $recentAnnouncements = Announcement::latest()->take(4)->get();
        $unreadMessages = auth()->user()->unreadMessagesCount();

        return view('admin.dashboard', compact(
            'stats', 'warnings', 'departments', 'gradeDistribution',
            'recentUsers', 'recentAnnouncements', 'unreadMessages'
        ));
    }
}