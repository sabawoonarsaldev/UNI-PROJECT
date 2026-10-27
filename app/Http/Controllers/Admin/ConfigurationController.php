<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\SemesterType;
use App\Models\Subject;
use App\Models\ClassGroup;

class ConfigurationController extends Controller
{
    public function index()
    {

    $sections = [
    [
        'title' => 'Faculties',
        'description' => 'Add new faculties and manage the existing ones.',
        'icon' => 'building-library',
        'count' => Faculty::count(),
        'manage' => route('admin.faculties.index'),
        'add' => route('admin.faculties.create'),
        'add_label' => 'Add faculty',
    ],
    [
        'title' => 'Departments',
        'description' => 'Add departments to a faculty and manage them.',
        'icon' => 'squares',
        'count' => Department::count(),
        'manage' => route('admin.departments.index'),
        'add' => route('admin.departments.create'),
        'add_label' => 'Add department',
    ],
    [
        'title' => 'Classes',
        'description' => 'Create a class with its first semester, add students and move it through the 8 semesters.',
        'icon' => 'academic-cap',
        'count' => ClassGroup::count(),
        'manage' => route('admin.classes.index'),
        'add' => route('admin.classes.create'),
        'add_label' => 'Add class',
    ],
    [
        'title' => 'Subjects',
        'description' => 'Add subjects to the current semester of a class and assign teachers.',
        'icon' => 'book-open',
        'count' => Subject::count(),
        'manage' => route('admin.subjects.index'),
        'add' => route('admin.subjects.create'),
        'add_label' => 'Add subject',
    ],
    [
        'title' => 'Academic Terms',
        'description' => 'The terms used for semesters, for example Spring and Fall.',
        'icon' => 'calendar',
        'count' => SemesterType::count(),
        'manage' => route('admin.semester-types.index'),
        'add' => null,
        'add_label' => null,
    ],
];
        return view('admin.configuration.index', compact('sections'));
    }
}