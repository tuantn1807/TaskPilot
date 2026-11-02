<?php

namespace ProjectsManager;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    //
    protected $fillable = [
        'name',
        'project_id',
        'user_id',
        'days',
        'hours',
        'company_id', 'description', 'status', 'order_index', 'priority', 'issue_type', 'assignee_id', 'reporter_id',
        'issue_key',
        'due_date', 'start_date', 'end_date', 'story_points', 'labels', 'sprint', 'parent_id', 'sprint_id',
        'test_result', 'test_status', 'approved_by', 'approved_at', 'approval_signature', 'approval_role',
    ];

    protected $casts = [
        'due_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'story_points' => 'decimal:1',
        'approved_at' => 'datetime',
    ];



    public function user(){
		return $this->belongsTo(User::class);
    }

    public function project(){
		return $this->belongsTo(Project::class);
    }

    public function company(){
		return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function comments()
    {
        return $this->morphMany('ProjectsManager\Comment', 'commentable');
    }

    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function sprintRelation() { return $this->belongsTo(Sprint::class, 'sprint_id'); }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function worklogs() { return $this->hasMany(Worklog::class); }
    public function skills() { return $this->hasMany(TaskSkill::class, 'task_id'); }
    public function aiRecommendations() { return $this->hasMany(AiAssigneeRecommendation::class, 'task_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function developmentLinks() { return $this->hasMany(ProjectDevelopmentLink::class, 'task_id')->latest('linked_at'); }
    public function customFieldValues() { return $this->hasMany(TaskCustomFieldValue::class, 'task_id')->with('customField'); }
    public function links() { return $this->hasMany(TaskLink::class, 'source_task_id')->with('targetTask'); }
    public function blockers() { return $this->hasMany(TaskLink::class, 'source_task_id')->where('link_type', 'is_blocked_by')->with('targetTask'); }
    public function isBlocked(): bool
    {
        $doneStatuses = ['done', 'closed', 'completed', 'resolved'];
        return $this->blockers()
            ->whereHas('targetTask', function ($q) use ($doneStatuses) {
                $q->whereNotIn('status', $doneStatuses);
            })
            ->exists();
    }
}

