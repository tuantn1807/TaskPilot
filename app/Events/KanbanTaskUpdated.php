<?php

namespace ProjectsManager\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProjectsManager\Task;
use ProjectsManager\User;

class KanbanTaskUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $projectId;
    public array $taskPayload;
    public array $changes;
    public ?array $actor;

    public function __construct(Task $task, array $changes = [], ?User $actor = null)
    {
        $task->loadMissing(['assignee', 'sprint']);
        $this->projectId = (int) $task->project_id;
        $this->changes = $changes;
        $this->actor = $actor ? [
            'id' => $actor->id,
            'name' => $actor->full_name ?? $actor->name,
        ] : null;

        $this->taskPayload = [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'sprint_id' => $task->sprint_id,
            'name' => $task->name,
            'issue_key' => $task->issue_key ?? ('TASK-' . $task->id),
            'issue_type' => $task->issue_type ?? 'task',
            'status' => $task->status ?? 'todo',
            'priority' => $task->priority ?? 'medium',
            'story_points' => $task->story_points,
            'labels' => $task->labels,
            'assignee_id' => $task->assignee_id,
            'assignee_name' => $task->assignee ? ($task->assignee->full_name ?? $task->assignee->name) : null,
            'updated_at' => optional($task->updated_at)->toIso8601String(),
            'updated_at_ts' => optional($task->updated_at)->timestamp ?? time(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('project.' . $this->projectId . '.kanban'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'kanban.task.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->projectId,
            'task' => $this->taskPayload,
            'changes' => $this->changes,
            'actor' => $this->actor,
        ];
    }
}

