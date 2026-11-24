<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Admin\Concerns\FiltersByClass;

class ClassScheduleController extends Controller
{
    use FiltersByClass;
    private const DAYS = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

public function index(Request $request)
{
    $schedules = ClassSchedule::with([
            'subject.semester.classGroup.department',
            'subject.semester.classGroup.entryTerm',
            'subject.teacher',
        ])
        // The same filters as the other pages: faculty, department, class, semester
        ->whereHas('subject', fn ($subject) => $this->applyClassFilters($subject, $request))
        ->orderByRaw("FIELD(day, '" . implode("','", self::DAYS) . "')")
        ->orderBy('start_time')
        ->paginate(15)
        ->withQueryString();

    return view('admin.schedules.index', ['schedules' => $schedules] + $this->classFilterOptions($request));
}


    public function create()
    {
        return view('admin.schedules.create', $this->formData());
    }

    public function store(Request $request)
    {
        ClassSchedule::create($this->validated($request));

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule created successfully.');
    }

    public function edit(ClassSchedule $schedule)
    {
        return view('admin.schedules.edit', $this->formData() + ['schedule' => $schedule]);
    }

    public function update(Request $request, ClassSchedule $schedule)
    {
        $schedule->update($this->validated($request, $schedule));

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule updated successfully.');
    }

    public function destroy(ClassSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule deleted successfully.');
    }

private function formData(): array
{
    // When a schedule is edited, its own subject stays in the list even if its semester is closed
    $own = request()->route('schedule')?->subject_id;

    // The subjects of the current semester of the classes
    $subjects = Subject::with('semester')
        ->whereHas('semester', fn ($semester) => $semester->where('is_active', true)->whereNotNull('class_group_id'))
        ->when($own, fn ($query) => $query->orWhere('id', $own))
        ->orderBy('code')
        ->get();

    return [
        'faculties' => \App\Models\Faculty::orderBy('name')->get(),
        'departments' => \App\Models\Department::orderBy('name')->get(['id', 'name', 'faculty_id']),
        'classes' => \App\Models\ClassGroup::with(['department', 'entryTerm'])->orderByDesc('entry_year')->get(),
        'subjects' => $subjects,
        'days' => self::DAYS,
    ];
}
    private function validated(Request $request, ?ClassSchedule $schedule = null): array
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'day' => ['required', Rule::in(self::DAYS)],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:50',
        ]);

        if ($conflict = $this->findConflict($data, $schedule?->id)) {
            throw ValidationException::withMessages(['start_time' => $conflict]);
        }

        return $data;
    }

    // Returns a message when the new time overlaps with another class, otherwise null
    private function findConflict(array $data, ?int $ignoreId): ?string
    {
        $subject = Subject::findOrFail($data['subject_id']);

        $overlapping = ClassSchedule::with('subject')
            ->where('day', $data['day'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get();

        foreach ($overlapping as $other) {
            $otherSubject = $other->subject;

            if ($subject->teacher_id && $otherSubject->teacher_id == $subject->teacher_id) {
                return "The teacher already teaches {$otherSubject->code} at this time.";
            }

            if (! empty($data['room']) && $other->room && strcasecmp($other->room, $data['room']) === 0) {
                return "Room {$other->room} is already used by {$otherSubject->code} at this time.";
            }

            if ($otherSubject->department_id == $subject->department_id
                && $otherSubject->semester_id == $subject->semester_id) {
                return "Students of this semester already have {$otherSubject->code} at this time.";
            }
        }

        return null;
    }
}