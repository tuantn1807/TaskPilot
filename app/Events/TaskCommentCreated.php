<?php

namespace ProjectsManager\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProjectsManager\Comment;

class TaskCommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $commentableId;
    public string $commentableType;
    public ?int $projectId;
    public string $action;
    public array $commentPayload;

    public function __construct(Comment $comment, string $action = 'created', ?int $projectId = null)
    {
        $comment->loadMissing('user');
        $this->commentableId = (int) $comment->commentable_id;
        $this->commentableType = (string) $comment->commentable_type;
        $this->projectId = $projectId;
        $this->action = $action;

        $userName = $comment->user ? ($comment->user->full_name ?? $comment->user->name) : 'Thành viên';
        $this->commentPayload = [
            'id' => $comment->id,
            'body' => (string) $comment->body,
            'user_id' => $comment->user_id,
            'user_name' => $userName,
            'user_initials' => strtoupper(mb_substr($userName, 0, 2)),
            'created_at_human' => $comment->created_at ? $comment->created_at->diffForHumans() : 'Vừa xong',
            'updated_at_ts' => optional($comment->updated_at)->timestamp ?? time(),
        ];
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('task.' . $this->commentableId . '.comments'),
        ];

        if ($this->projectId) {
            $channels[] = new Channel('project.' . $this->projectId . '.kanban');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'task.comment.' . $this->action;
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'commentable_id' => $this->commentableId,
            'commentable_type' => $this->commentableType,
            'project_id' => $this->projectId,
            'comment' => $this->commentPayload,
        ];
    }
}

