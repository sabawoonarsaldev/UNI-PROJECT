<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $fillable = [
    'name', 'email', 'password', 'role', 'department_id', 'roll_number', 'current_semester_id','class_group_id','academic_status','supervisor_id'
];

public function department()
{
    return $this->belongsTo(Department::class);
}

public function currentSemester()
{
    return $this->belongsTo(Semester::class, 'current_semester_id');
}

public function subjectsTaught()
{
    return $this->hasMany(Subject::class, 'teacher_id');
}

public function enrollments()
{
    return $this->hasMany(Enrollment::class, 'student_id');
}

public function grades()
{
    return $this->hasMany(Grade::class, 'student_id');
}

public function unreadMessagesCount(): int
{
    return Message::where('receiver_id', $this->id)
        ->where('is_read', false)
        ->count();
}

public function classGroup()
{
    return $this->belongsTo(ClassGroup::class);
}

public function hasAccount(): bool
{
    return ! is_null($this->password);
}

public function scopeWithAccount($query)
{
    return $query->whereNotNull('password');
}

public function supervisor()
{
    return $this->belongsTo(User::class, 'supervisor_id');
}

public function supervisedStudents()
{
    return $this->hasMany(User::class, 'supervisor_id');
}

public function project()
{
    return $this->hasOne(Project::class, 'student_id');
}

// Every word must be found in the name, email, roll number or department name
public function scopeSearch($query, ?string $term)
{
    $words = collect(preg_split('/\s+/u', trim((string) $term)))->filter()->take(6);

    foreach ($words as $word) {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $word) . '%';

        $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('roll_number', 'like', $like)
                ->orWhereHas('department', fn ($department) => $department->where('name', 'like', $like));
        });
    }

    return $query;
}

}
