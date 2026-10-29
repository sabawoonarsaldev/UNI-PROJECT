<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    // List of subjects with the number of enrolled students
    public function index(Request $request)
    {
        $subjects = Subject::with(['department', 'semester', 'teacher'])
            ->withCount('enrollments')
            ->when($request->department_id, fn ($query, $id) => $query->where('department_id', $id))
            ->orderBy('department_id')
            ->orderBy('semester_id')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('admin.enrollments.index', compact('subjects', 'departments'));
    }

    // Manage the students of one subject
    public function manage(Subject $subject)
    {
        $subject->load(['department', 'semester', 'teacher']);

        $enrollments = $subject->enrollments()
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student->name);

        $eligibleStudents = $this->eligibleStudents($subject)->get();

        return view('admin.enrollments.manage', compact('subject', 'enrollments', 'eligibleStudents'));
    }

    // Enroll the selected students
    public function store(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
        ]);

        // Only students who are really eligible can be enrolled
        $requestedIds = collect($validated['student_ids'])->map(fn ($id) => (int) $id);
        $ids = $this->eligibleStudents($subject)->pluck('id')->intersect($requestedIds);

        $this->enroll($subject, $ids);

        return redirect()->route('admin.enrollments.manage', $subject)
            ->with('success', $ids->count() . ' student(s) enrolled successfully.');
    }

    // Enroll every eligible student at once
    public function enrollAll(Subject $subject)
    {
        $ids = $this->eligibleStudents($subject)->pluck('id');

        $this->enroll($subject, $ids);

        return redirect()->route('admin.enrollments.manage', $subject)
            ->with('success', $ids->count() . ' student(s) enrolled successfully.');
    }

    // Remove one enrollment together with its grade
    public function destroy(Enrollment $enrollment)
    {
        $subject = $enrollment->subject;

        $enrollment->grade?->delete();
        $enrollment->delete();

        return redirect()->route('admin.enrollments.manage', $subject)
            ->with('success', 'Enrollment removed.');
    }

    // Active students of the class of this subject who are not enrolled yet
    private function eligibleStudents(Subject $subject)
    {
        return User::where('role', 'student')
            ->where('academic_status', 'active')
            ->whereNotNull('class_group_id')
            ->where('class_group_id', $subject->semester->class_group_id)
            ->whereDoesntHave('enrollments', fn ($query) => $query->where('subject_id', $subject->id))
            ->orderBy('name');
    }

    private function enroll(Subject $subject, $studentIds): void
    {
        foreach ($studentIds as $studentId) {
            Enrollment::create([
                'student_id' => $studentId,
                'subject_id' => $subject->id,
                'semester_id' => $subject->semester_id,
            ]);
        }
    }
}