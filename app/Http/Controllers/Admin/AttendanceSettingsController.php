<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Semester;
use App\Services\AttendanceService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Admin\Concerns\FiltersByClass;

class AttendanceSettingsController extends Controller
{

use FiltersByClass;
    
public function edit(Request $request)
{
    // The current semester of every active class, filtered by faculty, department and class
    $semesters = Semester::where('is_active', true)
        ->whereNotNull('class_group_id')
        ->when($request->query('class_group_id'), fn ($query, $id) => $query->where('class_group_id', $id))
        ->whereHas('classGroup', function ($classGroup) use ($request) {
            $classGroup
                ->when($request->query('faculty_id'), function ($q, $id) {
                    $q->whereHas('department', fn ($department) => $department->where('faculty_id', $id));
                })
                ->when($request->query('department_id'), fn ($q, $id) => $q->where('department_id', $id));
        })
        ->with(['classGroup.department', 'classGroup.entryTerm'])
        ->get()
        ->sortBy(fn ($semester) => $semester->classGroup->name)
        ->values();

    return view('admin.attendance.settings', [
        'holidays' => AttendanceService::holidays(),
        'minPercent' => AttendanceService::minPercent(),
        'days' => AttendanceService::DAYS,
        'semesters' => $semesters,
    ] + $this->classFilterOptions($request));
}


    public function update(Request $request)
    {
        $data = $request->validate([
            'holidays' => ['nullable', 'array'],
            'holidays.*' => ['string', Rule::in(AttendanceService::DAYS)],
            'min_percent' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $old = ['holidays' => AttendanceService::holidays(), 'min_percent' => AttendanceService::minPercent()];
        $new = ['holidays' => array_values($data['holidays'] ?? []), 'min_percent' => (int) $data['min_percent']];

        Settings::set('attendance.holidays', $new['holidays']);
        Settings::set('attendance.min_percent', $new['min_percent']);

        if ($old != $new) {
            AuditLog::record('settings.attendance', 'Attendance settings were changed.', null, $old, $new);
        }

        return back()->with('success', 'Attendance settings saved.');
    }

    public function updateSemester(Request $request, Semester $semester)
    {
        abort_unless($semester->class_group_id, 404);

        $data = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'weeks' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $old = ['start_date' => $semester->start_date, 'weeks' => $semester->weeks];
        $new = ['start_date' => $data['start_date'] ?? null, 'weeks' => (int) $data['weeks']];

        $semester->forceFill($new)->save();

        if ($old != $new) {
            AuditLog::record(
                'settings.semester_dates',
                "Dates of {$semester->name} were changed ({$semester->classGroup->name}).",
                $semester,
                $old,
                $new
            );
        }

        return back()->with('success', 'Dates of ' . $semester->name . ' saved.');
    }
}