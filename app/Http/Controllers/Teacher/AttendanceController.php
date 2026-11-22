<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Admin\Concerns\FiltersByClass;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    use FiltersByClass;

    // The subjects of the teacher, each one has its own attendance
    public function index(Request $request)
    {
        $teacherId = auth()->id();

        $query = Subject::where('teacher_id', $teacherId)
            ->with(['semester.classGroup.department', 'semester.classGroup.entryTerm']);

        $subjects = $this->orderByClass($this->applyClassFilters($query, $request))->get();
        $subjects->each(fn ($subject) => $subject->setAttribute('attendance', AttendanceService::summary($subject)));

        $options = $this->classFilterOptions($request, fn () => Subject::where('teacher_id', $teacherId));

        return view('teacher.attendance.index', ['subjects' => $subjects] + $options);
    }

    public function show(Subject $subject)
    {
        $this->authorizeSubject($subject);

        $subject->load(['semester.classGroup.department', 'semester.classGroup.entryTerm', 'schedules']);

        $sessions = $subject->attendanceSessions()
            ->withCount([
                'records as present_count' => fn ($query) => $query->where('status', 'present'),
                'records as absent_count' => fn ($query) => $query->where('status', 'absent'),
                'records as excused_count' => fn ($query) => $query->where('status', 'excused'),
            ])
            ->orderByDesc('date')
            ->get();

        return view('teacher.attendance.show', [
            'subject' => $subject,
            'sessions' => $sessions,
            'summary' => AttendanceService::summary($subject),
        ]);
    }

    // Does the teacher allow the students to see their attendance?
    public function visibility(Request $request, Subject $subject)
    {
        $this->authorizeSubject($subject);

        $subject->forceFill(['attendance_visible' => $request->boolean('attendance_visible')])->save();

        return back()->with('success', $subject->attendance_visible
            ? 'The students can now see their attendance of this subject.'
            : 'The students can no longer see their attendance of this subject.');
    }

    public function create(Subject $subject)
    {
        $this->authorizeSubject($subject);
        $this->ensureSemesterOpen($subject);

        return view('teacher.attendance.form', $this->formData($subject, null));
    }

    public function store(Request $request, Subject $subject)
    {
        $this->authorizeSubject($subject);
        $this->ensureSemesterOpen($subject);

        $data = $this->validated($request, $subject);

        $session = AttendanceSession::create([
            'subject_id' => $subject->id,
            'date' => $data['date'],
            'taken_by' => auth()->id(),
        ]);

        $this->saveRecords($session, $subject, $request);

        return redirect()->route('teacher.attendance.show', $subject)
            ->with('success', 'Attendance of ' . $data['date'] . ' was saved.');
    }

    // A locked day can be opened to look at, but not changed
    public function edit(AttendanceSession $attendanceSession)
    {
        $subject = $this->sessionSubject($attendanceSession, false);

        return view('teacher.attendance.form', $this->formData($subject, $attendanceSession->load('records')));
    }

    public function update(Request $request, AttendanceSession $attendanceSession)
    {
        $subject = $this->sessionSubject($attendanceSession, true);

        $data = $this->validated($request, $subject, $attendanceSession);

        $attendanceSession->update(['date' => $data['date']]);
        $this->saveRecords($attendanceSession, $subject, $request);

        return redirect()->route('teacher.attendance.show', $subject)
            ->with('success', 'Attendance of ' . $data['date'] . ' was updated.');
    }

    public function destroy(AttendanceSession $attendanceSession)
    {
        $subject = $this->sessionSubject($attendanceSession, true);

        $attendanceSession->delete();

        return redirect()->route('teacher.attendance.show', $subject)
            ->with('success', 'The day was deleted.');
    }

    private function authorizeSubject(Subject $subject): void
    {
        abort_unless($subject->teacher_id == auth()->id(), 403);
    }

    private function ensureSemesterOpen(Subject $subject): void
    {
        $subject->loadMissing('semester');

        abort_unless($subject->semester->is_active, 403, 'The semester of this subject is closed.');
    }

    // The subject of a day. When the day will be changed, it must be neither locked nor in a closed semester.
    private function sessionSubject(AttendanceSession $session, bool $mustBeEditable): Subject
    {
        $subject = $session->subject()->with('semester')->first();

        $this->authorizeSubject($subject);

        if ($mustBeEditable) {
            abort_if($session->isLocked(), 403, 'This day is locked by the administration.');
            abort_unless($subject->semester->is_active, 403, 'The semester of this subject is closed.');
        }

        return $subject;
    }

    private function formData(Subject $subject, ?AttendanceSession $session): array
    {
        $subject->loadMissing(['semester', 'schedules']);

        $enrollments = $subject->enrollments()
            ->where('chance', 1)
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student->name)
            ->values();

        // The latest date that can be chosen: today, or the end of the semester when it is earlier
        $end = AttendanceService::semesterEnd($subject);
        $maxDate = ($end && $end->lt(now())) ? $end : now();

        return [
            'subject' => $subject,
            'attendanceSession' => $session,
            'enrollments' => $enrollments,
            'records' => $session ? $session->records->keyBy('student_id') : collect(),
            'date' => $session ? $session->date->format('Y-m-d') : now()->format('Y-m-d'),
            'minDate' => AttendanceService::semesterStart($subject)?->format('Y-m-d'),
            'maxDate' => $maxDate->format('Y-m-d'),
            'holidays' => AttendanceService::holidays(),
            'scheduledDays' => $subject->schedules->pluck('day')->unique()->values(),
        ];
    }

    private function validated(Request $request, Subject $subject, ?AttendanceSession $session = null): array
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'status' => ['nullable', 'array'],
            'status.*' => ['nullable', Rule::in(['present', 'absent', 'excused'])],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:255'],
        ]);

        if ($problem = AttendanceService::dateProblem($subject, $data['date'], $session?->id)) {
            throw ValidationException::withMessages(['date' => $problem]);
        }

        return $data;
    }

    private function saveRecords(AttendanceSession $session, Subject $subject, Request $request): void
    {
        $statuses = $request->input('status', []);
        $notes = $request->input('note', []);

        // Only the students of the subject can be marked, so ids sent from outside are ignored
        $studentIds = $subject->enrollments()->where('chance', 1)->pluck('student_id');

        foreach ($studentIds as $studentId) {
            $status = $statuses[$studentId] ?? null;

            if (! $status) {
                // Blank: this student is not counted for this day
                AttendanceRecord::where('attendance_session_id', $session->id)
                    ->where('student_id', $studentId)
                    ->delete();

                continue;
            }

            AttendanceRecord::updateOrCreate(
                ['attendance_session_id' => $session->id, 'student_id' => $studentId],
                ['status' => $status, 'note' => $notes[$studentId] ?? null]
            );
        }
    }
}