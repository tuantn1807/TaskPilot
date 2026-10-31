<?php

namespace ProjectsManager\Observers;

use ProjectsManager\Comment;
use ProjectsManager\Events\TaskCommentCreated;
use ProjectsManager\Services\ActivityLogger;
use ProjectsManager\Services\NotificationService;
use ProjectsManager\User;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        $projectId = $this->resolveProjectId($comment);

        try {
            event(new TaskCommentCreated($comment, 'created', $projectId));
        } catch (\Throwable $e) {
            // Continue even if broadcasting driver is not active
        }

        if (! $projectId) {
            return;
        }

        ActivityLogger::record(
            projectId: $projectId,
            action: ActivityLogger::ACTION_COMMENTED,
            userId: $comment->user_id,
            subject: $comment,
            meta: mb_substr(strip_tags((string) $comment->body), 0, 120),
        );

        $project = \ProjectsManager\Project::find($projectId);
        if (! $project) {
            return;
        }

        $actor = User::find($comment->user_id);
        $url = $this->resolveUrl($comment);
        $subjectLabel = $this->resolveSubjectLabel($comment);

        // Priority @Mention notifications
        NotificationService::notifyMentions(
            content: (string) $comment->body,
            project: $project,
            actor: $actor,
            subjectLabel: 'bình luận trên ' . $subjectLabel,
            url: $url ? ($url . '#comment-' . $comment->id) : null,
            extraData: ['comment_id' => $comment->id, 'issue_type' => $comment->commentable_type],
        );

        $plainSnippet = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $comment->body), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        NotificationService::sendToProject(
            $project,
            NotificationService::EVENT_COMMENT_CREATED,
            'Bình luận mới trên ' . $subjectLabel,
            $actor?->name . ' đã bình luận: ' . mb_substr($plainSnippet, 0, 120) . (mb_strlen($plainSnippet) > 120 ? '...' : ''),
            $actor,
            ['issue_type' => $comment->commentable_type],
            $url,
        );
    }

    public function updated(Comment $comment): void
    {
        $projectId = $this->resolveProjectId($comment);

        try {
            event(new TaskCommentCreated($comment, 'updated', $projectId));
        } catch (\Throwable $e) {
        }

        if (! $projectId) {
            return;
        }

        $changes = $comment->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        ActivityLogger::record(
            projectId: $projectId,
            action: ActivityLogger::ACTION_COMMENT_EDITED,
            userId: auth()->id(),
            subject: $comment,
            changes: array_intersect_key($changes, array_flip(['body'])),
        );

        $project = \ProjectsManager\Project::find($projectId);
        if (! $project) {
            return;
        }

        $actor = User::find(auth()->id());
        $url = $this->resolveUrl($comment);
        $subjectLabel = $this->resolveSubjectLabel($comment);

        if (array_key_exists('body', $changes)) {
            $originalBody = (string) ($comment->getOriginal('body') ?? '');
            NotificationService::notifyMentions(
                content: (string) $comment->body,
                project: $project,
                actor: $actor,
                subjectLabel: 'bình luận trên ' . $subjectLabel,
                url: $url ? ($url . '#comment-' . $comment->id) : null,
                extraData: ['comment_id' => $comment->id, 'issue_type' => $comment->commentable_type],
                previousContent: $originalBody,
            );
        }

        NotificationService::sendToProject(
            $project,
            NotificationService::EVENT_COMMENT_UPDATED,
            'Bình luận được chỉnh sửa trên ' . $subjectLabel,
            $actor?->name . ' đã chỉnh sửa bình luận trên ' . $subjectLabel . '.',
            $actor,
            ['issue_type' => $comment->commentable_type],
            $url,
        );
    }

    public function deleted(Comment $comment): void
    {
        $projectId = $this->resolveProjectId($comment);
        if (! $projectId) {
            return;
        }

        ActivityLogger::record(
            projectId: $projectId,
            action: ActivityLogger::ACTION_COMMENT_DELETED,
            userId: auth()->id(),
            subjectLabel: 'Comment',
        );

        $project = \ProjectsManager\Project::find($projectId);
        if (! $project) {
            return;
        }

        $actor = User::find(auth()->id());
        $subjectLabel = $this->resolveSubjectLabel($comment);

        NotificationService::sendToProject(
            $project,
            NotificationService::EVENT_COMMENT_DELETED,
            'Bình luận bị xóa trên ' . $subjectLabel,
            $actor?->name . ' đã xóa một bình luận trên ' . $subjectLabel . '.',
            $actor,
            ['issue_type' => $comment->commentable_type],
            $project->id ? '/projects/' . $project->id : null,
        );
    }

    private function resolveProjectId(Comment $comment): ?int
    {
        if ($comment->commentable_type === \ProjectsManager\Task::class) {
            $task = \ProjectsManager\Task::find($comment->commentable_id);
            return $task?->project_id;
        }

        if ($comment->commentable_type === \ProjectsManager\Project::class) {
            return (int) $comment->commentable_id;
        }

        return null;
    }

    private function resolveUrl(Comment $comment): ?string
    {
        if ($comment->commentable_type === \ProjectsManager\Task::class) {
            $task = \ProjectsManager\Task::find($comment->commentable_id);
            return $task ? '/tasks/' . $task->id : null;
        }

        if ($comment->commentable_type === \ProjectsManager\Project::class) {
            return '/projects/' . $comment->commentable_id;
        }

        return null;
    }

    private function resolveSubjectLabel(Comment $comment): string
    {
        if ($comment->commentable_type === \ProjectsManager\Task::class) {
            $task = \ProjectsManager\Task::find($comment->commentable_id);
            return 'công việc "' . ($task?->name ?? 'Task #' . $comment->commentable_id) . '"';
        }

        if ($comment->commentable_type === \ProjectsManager\Project::class) {
            $project = \ProjectsManager\Project::find($comment->commentable_id);
            return 'dự án "' . ($project?->name ?? 'Project #' . $comment->commentable_id) . '"';
        }

        return 'một đối tượng';
    }
}
