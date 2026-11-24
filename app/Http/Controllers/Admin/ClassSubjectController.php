<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClassSubjectController extends Controller
{
    // Chooses subjects of the curriculum for the current semester of a class
    public function store(Request $request, ClassGroup $classGroup, Semester $semester)
    {
        $this->ensureOpen($classGroup, $semester);

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.selected' => ['nullable', 'boolean'],
            'items.*.credit_hours' => ['nullable', 'integer', 'min:1', 'max:10'],
            'items.*.teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')],
        ]);

        $chosen = collect($data['items'])->filter(fn ($item) => ! empty($item['selected']));

        if ($chosen->isEmpty()) {
            return back()->with('error', 'Select at least one subject.');
        }

        // Only subjects of the curriculum of this department and this semester number
        $curriculum = CurriculumSubject::where('department_id', $classGroup->department_id)
            ->where('semester_number', $semester->number)
            ->whereIn('id', $chosen->keys())
            ->get();

        $existingCodes = $semester->subjects()->pluck('code');

        $studentIds = User::where('role', 'student')
            ->where('academic_status', 'active')
            ->where('class_group_id', $classGroup->id)
            ->pluck('id');

        $created = 0;

        DB::transaction(function () use ($curriculum, $chosen, $existingCodes, $classGroup, $semester, $studentIds, &$created) {
            foreach ($curriculum as $item) {
                if ($existingCodes->contains($item->code)) {
                    continue;
                }

                $input = $chosen[$item->id];

                // The class gets its own copy: the teacher and the credits can differ from the curriculum
                $subject = Subject::forceCreate([
                    'name' => $item->name,
                    'code' => $item->code,
                    'department_id' => $classGroup->department_id,
                    'semester_id' => $semester->id,
                    'curriculum_subject_id' => $item->id,
                    'teacher_id' => $input['teacher_id'] ?? $item->teacher_id,
                    'credit_hours' => $input['credit_hours'] ?? $item->credit_hours,
                ]);

                // Every active student of the class is enrolled in the new subject
                foreach ($studentIds as $studentId) {
                    Enrollment::create([
                        'student_id' => $studentId,
                        'subject_id' => $subject->id,
                        'semester_id' => $semester->id,
                    ]);
                }

                $created++;
            }
        });

        AuditLog::record(
            'class.subjects_selected',
            "{$created} subject(s) were chosen for {$semester->name} of {$classGroup->name}.",
            $classGroup
        );

        return redirect()->route('admin.classes.show', [$classGroup, 'semester' => $semester->number])
            ->with('success', "{$created} subject(s) added to this semester and {$studentIds->count()} student(s) enrolled in each.");
    }

    // The teacher and the credits of a subject of this class
    public function update(Request $request, ClassGroup $classGroup, Subject $subject)
    {
        abort_unless($subject->semester->class_group_id === $classGroup->id, 404);

        $data = $request->validate([
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $subject->update($data);

        return redirect()->route('admin.classes.show', [$classGroup, 'semester' => $subject->semester->number])
            ->with('success', "{$subject->code} was updated.");
    }

    // A subject can be removed from the class only while nothing was done in it
    public function destroy(ClassGroup $classGroup, Subject $subject)
    {
        abort_unless($subject->semester->class_group_id === $classGroup->id, 404);

        $back = redirect()->route('admin.classes.show', [$classGroup, 'semester' => $subject->semester->number]);

        if (Grade::where('subject_id', $subject->id)->exists() || AttendanceSession::where('subject_id', $subject->id)->exists()) {
            return $back->with('error', "{$subject->code} already has grades or attendance and cannot be removed.");
        }

        AuditLog::record('class.subject_removed', "{$subject->code} was removed from {$subject->semester->name} of {$classGroup->name}.", $classGroup);

        $subject->delete();

        return $back->with('success', 'Subject removed from this semester.');
    }

    // Subjects can be chosen only for the current semester of an active class
    private function ensureOpen(ClassGroup $classGroup, Semester $semester): void
    {
        abort_unless($semester->class_group_id === $classGroup->id, 404);
        abort_unless($classGroup->status === 'active' && $semester->is_active, 403, 'Only the current semester can receive subjects.');
    }
}