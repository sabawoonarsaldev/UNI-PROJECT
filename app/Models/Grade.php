<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class Grade extends Model
{
    protected $fillable = [
        'enrollment_id', 'student_id', 'subject_id', 'semester_id', 'chance',
        'exam_score', 'homework_score', 'activity_score', 'final_score',
        'marks', 'grade_letter',
    ];

    protected $casts = ['chance' => 'integer'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
    
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }


public const MAX_CHANCES = 4;
public const PASS_MARK = 55;

// The four parts of the total mark (together 100)
public const COMPONENTS = [
    'exam' => ['column' => 'exam_score', 'label' => 'Exams', 'max' => 20],
    'homework' => ['column' => 'homework_score', 'label' => 'Homework', 'max' => 10],
    'activity' => ['column' => 'activity_score', 'label' => 'Class activity', 'max' => 10],
    'final' => ['column' => 'final_score', 'label' => 'Final exam', 'max' => 60],
];

// Checks the scores of a grade form: every box has its own maximum,
// and a student gets either all four scores or none
public static function validateScores(Request $request): array
{
    $rules = ['scores' => ['array']];

    foreach (self::COMPONENTS as $key => $component) {
        $rules["scores.*.{$key}"] = ['nullable', 'numeric', 'min:0', "max:{$component['max']}"];
    }

    $scores = $request->validate($rules)['scores'] ?? [];

    foreach ($scores as $id => $row) {
        $filled = collect(array_keys(self::COMPONENTS))
            ->filter(fn ($key) => isset($row[$key]) && $row[$key] !== '')
            ->count();

        if ($filled > 0 && $filled < count(self::COMPONENTS)) {
            throw ValidationException::withMessages([
                "scores.{$id}" => 'Fill all four boxes of this student, or leave the row empty.',
            ]);
        }
    }

    return $scores;
}

public static function letterFor(float $marks): string
{
    return match (true) {
        $marks >= 90 => 'A+',
        $marks >= 80 => 'A',
        $marks >= 75 => 'B+',
        $marks >= 70 => 'B',
        $marks >= 65 => 'C+',
        $marks >= 60 => 'C',
        $marks >= self::PASS_MARK => 'D',
        default => 'F',
    };
}
    public static function pointsFor(string $letter): float
    {
        return match ($letter) {
            'A+' => 4.0,
            'A' => 3.7,
            'B+' => 3.3,
            'B' => 3.0,
            'C+' => 2.5,
            'C' => 2.0,
            'D' => 1.0,
            default => 0.0,
        };
    }
}