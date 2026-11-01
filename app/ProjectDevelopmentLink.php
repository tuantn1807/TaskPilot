<?php

namespace ProjectsManager;

use Illuminate\Database\Eloquent\Model;

class ProjectDevelopmentLink extends Model
{
    protected $table = 'project_development_links';

    protected $fillable = [
        'project_id',
        'user_id',
        'task_id',
        'type',
        'label',
        'branch_name',
        'commit_sha',
        'author_name',
        'repo_name',
        'url',
        'status',
        'linked_at',
    ];

    protected $casts = ['linked_at' => 'datetime'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
