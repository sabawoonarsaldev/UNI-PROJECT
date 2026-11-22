<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersByClass;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Subject;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use FiltersByClass;

    // Faculty -> department -> class -> semester, then the subjects of that semester
    public function index(Request $request)
    {
        $query = Subject::with(['semester.classGroup.department', 'semester.classGroup.entryTerm', 'teacher'])
            ->withCount(['attendanceSessions as locked_count' => fn ($q) => $q->whereNotNull('locked_at')]);

        $subjects = $this->orderByClass($this->applyClassFilters($query, $request))
            ->paginate(10)
            ->withQueryString();

        $subjects->getCollection()->each(
            fn ($subject) => $subject->setAttribute('attendance', AttendanceService::summary($subject))
        );

        return view('admin.attendance.index', ['subjects' => $subjects] + $this->classFilterOptions($request));
    }

    public function show(Subject $subject)
    {
        $subject->load(['semester.classGroup.department', 'semester.classGroup.entryTerm', 'teacher', 'schedules']);

        $sessions = $subject->attendanceSessions()
            ->with('locker')
            ->withCount([
                'records as present_count' => fn ($query) => $query->where('status', 'present'),
                'records as absent_count' => fn ($query) => $query->where('status', 'absent'),
                'records as excused_count' => fn ($query) => $query->where('status', 'excused'),
            ])
            ->orderByDesc('date')
            ->get();

        return view('admin.attendance.show', [
            'subject' => $subject,
            'sessions' => $sessions,
            'summary' => AttendanceService::summary($subject),
        ]);
    }

    // One day, read only
    public function session(AttendanceSession $attendanceSession)
    {
        $attendanceSession->load(['subject.semester.classGroup', 'taker', 'locker']);

        $subject = $attendanceSession->subject;
        $records = $attendanceSession->records()->get()->keyBy('student_id');

        $enrollments = $subject->enrollments()
            ->where('chance', 1)
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student->name)
            ->values();

        return view('admin.attendance.session', [
            'attendanceSession' => $attendanceSession,
            'subject' => $subject,
            'records' => $records,
            'enrollments' => $enrollments,
        ]);
    }

    public function lock(AttendanceSession $attendanceSession)
    {
        $attendanceSession->update(['locked_at' => now(), 'locked_by' => auth()->id()]);

        $subject = $attendanceSession->subject;
        AuditLog::record(
            'attendance.locked',
            "Attendance of {$subject->code} on {$attendanceSession->date->format('Y-m-d')} was locked.",
            $attendanceSession
        );

        return back()->with('success', 'The day ' . $attendanceSession->date->format('Y-m-d') . ' is locked. The teacher cannot change it.');
    }

    public function unlock(AttendanceSession $attendanceSession)
    {
        $attendanceSession->update(['locked_at' => null, 'locked_by' => null]);

        $subject = $attendanceSession->subject;
        AuditLog::record(
            'attendance.unlocked',
            "Attendance of {$subject->code} on {$attendanceSession->date->format('Y-m-d')} was unlocked.",
            $attendanceSession
        );

        return back()->with('success', 'The day ' . $attendanceSession->date->format('Y-m-d') . ' is open again.');
    }

    public function lockAll(Subject $subject)
    {
        $count = $subject->attendanceSessions()
            ->whereNull('locked_at')
            ->update(['locked_at' => now(), 'locked_by' => auth()->id()]);

        AuditLog::record('attendance.locked', "All {$count} open day(s) of {$subject->code} were locked.", $subject);

        return back()->with('success', "{$count} day(s) were locked.");
    }

    public function unlockAll(Subject $subject)
    {
        $count = $subject->attendanceSessions()
            ->whereNotNull('locked_at')
            ->update(['locked_at' => null, 'locked_by' => null]);

        AuditLog::record('attendance.unlocked', "All {$count} locked day(s) of {$subject->code} were unlocked.", $subject);

        return back()->with('success', "{$count} day(s) were unlocked.");
    }
}