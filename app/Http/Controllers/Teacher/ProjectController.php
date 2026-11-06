<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $students = auth()->user()->supervisedStudents()
            ->with(['project', 'classGroup.department', 'classGroup.entryTerm'])
            ->orderBy('name')
            ->get();

        // Filter by project status ("none" = the student has not started a project)
        $status = $request->query('status');

        if ($status) {
            $students = $students->filter(
                fn ($student) => $status === 'none' ? ! $student->project : $student->project?->status === $status
            );
        }

        return view('teacher.projects.index', compact('students'));
    }
}