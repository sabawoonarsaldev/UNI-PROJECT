<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemesterType extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }
}