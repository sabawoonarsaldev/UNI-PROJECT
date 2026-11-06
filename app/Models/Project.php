<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    public const TYPES = [
        'thesis' => 'Thesis',
        'research_project' => 'Research Project',
        'final_project' => 'Final Project',
    ];

    protected $fillable = [
        'student_id', 'supervisor_id', 'department_id', 'title', 'type', 'year',
        'abstract', 'status', 'submitted_at', 'approved_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function comments()
    {
        return $this->hasMany(ProjectComment::class);
    }

    public function keywords()
    {
        return $this->belongsToMany(Keyword::class);
    }

    public function downloads()
    {
        return $this->hasMany(ProjectDownload::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Every word must be found somewhere: title, abstract, keywords, student or supervisor
    public function scopeSearch($query, ?string $term)
    {
        $words = collect(preg_split('/\s+/u', trim((string) $term)))->filter()->take(6);

        foreach ($words as $word) {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $word) . '%';

            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('abstract', 'like', $like)
                    ->orWhereHas('keywords', fn ($k) => $k->where('name', 'like', $like))
                    ->orWhereHas('student', fn ($s) => $s->where('name', 'like', $like))
                    ->orWhereHas('supervisor', fn ($s) => $s->where('name', 'like', $like));
            });
        }

        return $query;
    }

    // The student may change the project only in these states
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'revision']);
    }

    // The newest document of the student is the document of the repository
    public function repositoryDocument(): ?ProjectFile
    {
        return $this->files()->where('kind', 'document')->latest('id')->first();
    }

    // Moves the project to a new status and writes it into the history
    public function transition(string $status, ?string $note = null): void
    {
        $attributes = ['status' => $status];

        if ($status === 'submitted') {
            $attributes['submitted_at'] = now();
        }
        if ($status === 'approved') {
            $attributes['approved_at'] = now();
        }

        $this->update($attributes);

        $this->comments()->create([
            'user_id' => auth()->id(),
            'type' => 'status',
            'status' => $status,
            'body' => $note,
        ]);
    }

    // "ai, machine learning" becomes two keywords (max 10, lower case, without duplicates)
    public function syncKeywords(?string $text): void
    {
        $names = collect(preg_split('/[,\x{060C};\r\n]+/u', (string) $text))
            ->map(fn ($name) => mb_strtolower(trim($name)))
            ->filter(fn ($name) => $name !== '' && mb_strlen($name) <= 50)
            ->unique()
            ->take(10);

        $ids = $names->map(fn ($name) => Keyword::firstOrCreate(['name' => $name])->id);

        $this->keywords()->sync($ids->all());
    }

    public function keywordsText(): string
    {
        return $this->keywords->pluck('name')->implode(', ');
    }
}