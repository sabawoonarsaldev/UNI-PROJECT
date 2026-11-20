<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Http\Controllers\Admin\Concerns\FiltersByClass;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    use FiltersByClass;
public function index(Request $request)
{
    $teacherId = auth()->id();

    $query = Subject::where('teacher_id', $teacherId)
        ->with(['semester.classGroup.department', 'semester.classGroup.entryTerm'])
        ->withCount('enrollments');

    $subjects = $this->orderByClass($this->applyClassFilters($query, $request))->get();

    // The dropdowns offer only the departments, classes and semesters where this teacher teaches
    $options = $this->classFilterOptions($request, fn () => Subject::where('teacher_id', $teacherId));

    return view('teacher.subjects.index', ['subjects' => $subjects] + $options);
}

   
    public function show(Subject $subject)
{
    // A teacher can only open his or her own subjects
    abort_unless($subject->teacher_id == auth()->id(), 403);

    $subject->load(['department', 'semester', 'schedules']);

    $rows = $subject->gradebook();

    return view('teacher.subjects.show', compact('subject', 'rows'));
}


}