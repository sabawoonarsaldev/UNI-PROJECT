<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
    'enrollment_id', 'student_id', 'subject_id', 'semester_id', 'chance', 'marks', 'grade_letter',
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