<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Subject;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
    public const DAYS = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    // Weekly holidays: no attendance is taken and no session is planned on these days
    public static function holidays(): array
    {
        return Settings::get('attendance.holidays', ['Friday']);
    }

    // A student must attend at least this percent of the sessions to sit in the exam
    public static function minPercent(): int
    {
        return (int) Settings::get('attendance.min_percent', 75);
    }

    public static function weeks(Subject $subject): int
    {
        $subject->loadMissing('semester');

        return (int) ($subject->semester->weeks ?: 16);
    }

    public static function semesterStart(Subject $subject): ?Carbon
    {
        $subject->loadMissing('semester');
        $start = $subject->semester->start_date;

        return $start ? Carbon::parse($start)->startOfDay() : null;
    }

    public static function semesterEnd(Subject $subject): ?Carbon
    {
        $start = self::semesterStart($subject);

        return $start ? $start->copy()->addWeeks(self::weeks($subject))->subDay() : null;
    }

    // How many sessions are planned and how many absences a student may have.
    // Planned sessions = weekly lessons of the subject (without holidays) x weeks of the semester.
    public static function plan(Subject $subject, int $held): array
    {
        $slots = $subject->schedules()->whereNotIn('day', self::holidays())->count();
        $weeks = self::weeks($subject);

        // Without a timetable, or when more sessions were held than planned, the held sessions count
        $planned = max($held, $slots * $weeks);
        $allowed = (int) floor($planned * (100 - self::minPercent()) / 100);

        return compact('slots', 'weeks', 'planned', 'allowed');
    }

    // The attendance of all students of a subject
    public static function summary(Subject $subject): array
    {
        $sessionIds = $subject->attendanceSessions()->pluck('id');
        $held = $sessionIds->count();
        $plan = self::plan($subject, $held);

        $records = AttendanceRecord::whereIn('attendance_session_id', $sessionIds)
            ->get()
            ->groupBy('student_id');

        // Only the first chance of a subject has lessons (retakes are only exams)
        $rows = $subject->enrollments()
            ->where('chance', 1)
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student->name)
            ->map(fn ($enrollment) => self::row(
                $enrollment->student,
                $records->get($enrollment->student_id, collect()),
                $plan
            ))
            ->values();

        return ['rows' => $rows, 'held' => $held, 'plan' => $plan];
    }

    // The numbers of one student. state: ok | warning (at the limit) | barred (over the limit)
    public static function row($student, Collection $records, array $plan): array
    {
        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $excused = $records->where('status', 'excused')->count();
        $counted = $present + $absent + $excused;

        $state = 'ok';

        if ($absent > $plan['allowed']) {
            $state = 'barred';
        } elseif ($plan['allowed'] > 0 && $absent >= $plan['allowed']) {
            $state = 'warning';
        }

        return [
            'student' => $student,
            'present' => $present,
            'absent' => $absent,
            'excused' => $excused,
            'counted' => $counted,
            // An excused day counts as attended; blank days are not counted at all
            'percent' => $counted > 0 ? round(($present + $excused) / $counted * 100, 1) : null,
            'allowed' => $plan['allowed'],
            'state' => $state,
        ];
    }

    // The attendance of one student with the list of days
    public static function forStudent(Subject $subject, int $studentId): array
    {
        $sessions = $subject->attendanceSessions()->orderBy('date')->get();

        $records = AttendanceRecord::whereIn('attendance_session_id', $sessions->pluck('id'))
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('attendance_session_id');

        $plan = self::plan($subject, $sessions->count());

        return [
            'summary' => self::row(null, $records->values(), $plan),
            'plan' => $plan,
            'held' => $sessions->count(),
            'days' => $sessions->map(fn ($session) => [
                'date' => $session->date,
                'status' => $records->get($session->id)?->status,
                'note' => $records->get($session->id)?->note,
            ])->values(),
        ];
    }

    // Returns the reason why a date cannot be used, or null when it is fine
    public static function dateProblem(Subject $subject, string $date, ?int $ignoreSessionId = null): ?string
    {
        $day = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();

        if ($day->isFuture()) {
            return 'You cannot take attendance for a future date.';
        }

        if (in_array($day->format('l'), self::holidays(), true)) {
            return $day->format('l') . ' is a weekly holiday.';
        }

        $start = self::semesterStart($subject);
        $end = self::semesterEnd($subject);

        if ($start && ($day->lt($start) || $day->gt($end))) {
            return 'This date is outside the semester (' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d') . ').';
        }

        $exists = $subject->attendanceSessions()
            ->whereDate('date', $day->toDateString())
            ->when($ignoreSessionId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->exists();

        return $exists ? 'Attendance for this date was already taken.' : null;
    }
}