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

public const ACADEMIC_RANKS = [
    'pohyalai' => 'Pohyalai (Assistant Lecturer)',
    'pohanyar' => 'Pohanyar (Lecturer)',
    'pohanmal' => 'Pohanmal (Assistant Professor)',
    'pohandoy' => 'Pohandoy (Associate Professor)',
    'pohanwal' => 'Pohanwal (Professor)',
];

public const EDUCATION_DEGREES = [
    'bachelor' => 'Bachelor',
    'master' => 'Master',
    'doctorate' => 'Doctorate (PhD)',
];

public const GENDERS = [
    'male' => 'Male',
    'female' => 'Female',
];
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
    'name', 'email', 'password', 'role', 'department_id', 'roll_number',
    'current_semester_id', 'class_group_id', 'academic_status', 'supervisor_id',
    'photo_path', 'father_name', 'date_of_birth', 'gender', 'phone', 'address',
    'academic_rank', 'education_degree', 'specialization', 'experience_years',
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

// Only the grades that the administration published: students must see only these
public function publishedGrades()
{
    return $this->hasMany(Grade::class, 'student_id')->where('status', 'published');
}


// The address of the photo (it is private: only the admin and the owner can open it)
public function photoUrl(): ?string
{
    return $this->photo_path
        ? route('users.photo', $this) . '?v=' . $this->updated_at?->timestamp
        : null;
}

// "Ahmad Rahimi" -> "AR", shown when the user has no photo
public function initials(): string
{
    return collect(preg_split('/\s+/u', trim($this->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
}


}
