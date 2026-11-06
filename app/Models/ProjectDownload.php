<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDownload extends Model
{
    protected $fillable = ['project_id', 'project_file_id', 'user_id'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function file()
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}