<?php

namespace App\Services;

use App\Models\Grade;
use Illuminate\Support\Collection;

class GpaCalculator
{
    // $grades: a collection of Grade models with the "subject" relation loaded.
    // GPA is weighted by credit hours. Subjects without a grade are ignored.
    public static function gpa(Collection $grades): ?float
    {
        $credits = 0;
        $points = 0;

        foreach ($grades as $grade) {
            if ($grade->grade_letter === null) {
                continue;
            }

            $credit = $grade->subject->credit_hours;
            $credits += $credit;
            $points += Grade::pointsFor($grade->grade_letter) * $credit;
        }

        return $credits > 0 ? round($points / $credits, 2) : null;
    }

    // Average of the marks (the percentage), also weighted by credit hours
    public static function average(Collection $grades): ?float
    {
        $credits = 0;
        $total = 0;

        foreach ($grades as $grade) {
            if ($grade->marks === null) {
                continue;
            }

            $credit = $grade->subject->credit_hours;
            $credits += $credit;
            $total += (float) $grade->marks * $credit;
        }

        return $credits > 0 ? round($total / $credits, 2) : null;
    }
}