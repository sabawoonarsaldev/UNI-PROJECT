<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProfileData
{
    // Everything the profile page needs: the person, the four number cards and the sections of details
    public static function for(User $user): array
    {
        $user->loadMissing(['department.faculty', 'classGroup', 'currentSemester', 'supervisor', 'project']);

        $faculty = $user->department?->faculty?->name;
        $department = $user->department?->name;
        $dob = $user->date_of_birth ? Carbon::parse($user->date_of_birth) : null;

        $personal = [
            ['Full name', $user->name],
            ['Father name', $user->father_name],
            ['Gender', User::GENDERS[$user->gender] ?? null],
            ['Date of birth', $dob ? $dob->format('Y-m-d') . ' (age ' . $dob->age . ')' : null],
            ['Phone', $user->phone],
            ['Email', $user->email],
            ['Address', $user->address],
        ];

        $account = [
            ['Login account', $user->hasAccount() ? 'Yes' : 'No'],
            ['Added to the system', $user->created_at?->format('Y-m-d')],
        ];

        if ($user->role === 'student') {
            // The student sees only the published grades, so the CGPA is calculated from them
            $cgpa = GpaCalculator::gpa($user->publishedGrades()->with('subject')->get());

            $stats = [
                ['Roll No.', $user->roll_number],
                ['Class', $user->classGroup?->name],
                ['Current semester', $user->currentSemester?->name],
                ['CGPA', $cgpa !== null ? number_format($cgpa, 2) : null],
            ];

            $academic = [
                ['Faculty', $faculty],
                ['Department', $department],
                ['Roll number', $user->roll_number],
                ['Class', $user->classGroup?->name],
                ['Current semester', $user->currentSemester?->name],
                ['Status', ['active' => 'Active', 'graduated' => 'Graduated', 'failed' => 'Complete failure'][$user->academic_status] ?? null],
                ['Supervisor', $user->supervisor?->name],
                ['Final project', $user->project
                    ? $user->project->title . ' (' . str_replace('_', ' ', $user->project->status) . ')'
                    : 'Not started'],
            ];

            return [
                'person' => $user,
                'stats' => $stats,
                'sections' => ['Personal information' => $personal, 'Academic information' => $academic, 'Account' => $account],
            ];
        }

        if ($user->role === 'teacher') {
            $rank = User::ACADEMIC_RANKS[$user->academic_rank] ?? null;

            $stats = [
                ['Academic rank', $rank ? Str::before($rank, ' (') : null],
                ['Subjects', $user->subjectsTaught()->count()],
                ['Students supervised', $user->supervisedStudents()->count()],
                ['Experience', $user->experience_years !== null ? $user->experience_years . ' year(s)' : null],
            ];

            $academic = [
                ['Faculty', $faculty],
                ['Department', $department],
                ['Academic rank', $rank],
                ['Education degree', User::EDUCATION_DEGREES[$user->education_degree] ?? null],
                ['Specialization', $user->specialization],
                ['Teaching experience', $user->experience_years !== null ? $user->experience_years . ' year(s)' : null],
                ['Subjects taught', $user->subjectsTaught()->count()],
                ['Students supervised', $user->supervisedStudents()->count()],
            ];

            return [
                'person' => $user,
                'stats' => $stats,
                'sections' => ['Personal information' => $personal, 'Academic information' => $academic, 'Account' => $account],
            ];
        }

        // Admin
        return [
            'person' => $user,
            'stats' => [],
            'sections' => [
                'Details' => [['Full name', $user->name], ['Email', $user->email], ['Phone', $user->phone]],
                'Account' => $account,
            ],
        ];
    }
}