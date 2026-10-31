<?php

namespace ProjectsManager\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use ProjectsManager\Notifications\TaskPilotNotification;
use ProjectsManager\Project;
use ProjectsManager\Task;
use ProjectsManager\User;

class NotificationService
{
    public const EVENT_TASK_CREATED = 'task.created';
    public const EVENT_TASK_UPDATED = 'task.updated';
    public const EVENT_TASK_DELETED = 'task.deleted';
    public const EVENT_TASK_ASSIGNED = 'task.assigned';
    public const EVENT_TASK_STATUS_CHANGED = 'task.status_changed';
    public const EVENT_COMMENT_CREATED = 'comment.created';
    public const EVENT_COMMENT_UPDATED = 'comment.updated';
    public const EVENT_COMMENT_DELETED = 'comment.deleted';
    public const EVENT_PROJECT_CREATED = 'project.created';
    public const EVENT_PROJECT_UPDATED = 'project.updated';
    public const EVENT_PROJECT_ARCHIVED = 'project.archived';
    public const EVENT_PROJECT_DELETED = 'project.deleted';
    public const EVENT_PROJECT_MEMBER_ADDED = 'project.member_added';
    public const EVENT_SPRINT_CREATED = 'sprint.created';
    public const EVENT_SPRINT_UPDATED = 'sprint.updated';
    public const EVENT_WORKLOG_CREATED = 'worklog.created';
    public const EVENT_TEAM_INVITATION = 'team.invitation';
    public const EVENT_USER_MENTIONED = 'user.mentioned';

    public static function sendToProject(
        Project $project,
        string $eventType,
        string $title,
        string $message,
        ?User $actor = null,
        ?array $data = null,
        ?string $url = null,
    ): void
    {
        $recipients = $project->relationLoaded('users')
            ? $project->users
            : $project->users()->get();

        $recipients = static::prepareRecipients($recipients, $actor);

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new TaskPilotNotification($eventType, $title, $message, $data, $url, $actor);

        static::safeSend($recipients, $notification);
    }

    public static function sendToAssignee(
        Task $task,
        string $eventType,
        string $title,
        string $message,
        ?User $actor = null,
        ?array $data = null,
        ?string $url = null,
    ): void
    {
        if (! $task->assignee) {
            return;
        }

        if ($actor && $task->assignee->id === $actor->id) {
            return;
        }

        $notification = new TaskPilotNotification($eventType, $title, $message, $data, $url, $actor);

        static::safeSend($task->assignee, $notification);
    }

    public static function sendToTaskCreator(
        Task $task,
        string $eventType,
        string $title,
        string $message,
        ?User $actor = null,
        ?array $data = null,
        ?string $url = null,
    ): void
    {
        if (! $task->user) {
            return;
        }

        if ($actor && $task->user->id === $actor->id) {
            return;
        }

        $notification = new TaskPilotNotification($eventType, $title, $message, $data, $url, $actor);

        static::safeSend($task->user, $notification);
    }

    public static function sendToUser(
        User $user,
        string $eventType,
        string $title,
        string $message,
        ?User $actor = null,
        ?array $data = null,
        ?string $url = null,
    ): void
    {
        $notification = new TaskPilotNotification($eventType, $title, $message, $data, $url, $actor);

        static::safeSend($user, $notification);
    }

    public static function sendToMany(
        Collection $users,
        string $eventType,
        string $title,
        string $message,
        ?User $actor = null,
        ?array $data = null,
        ?string $url = null,
    ): void
    {
        $users = static::prepareRecipients($users, $actor);

        if ($users->isEmpty()) {
            return;
        }

        $notification = new TaskPilotNotification($eventType, $title, $message, $data, $url, $actor);

        static::safeSend($users, $notification);
    }

    /**
     * Extract User models referenced via @mention inside HTML or plain text.
     */
    public static function extractMentionedUsers(?string $content, ?Project $project = null): Collection
    {
        $raw = trim((string) $content);
        if ($raw === '') {
            return collect();
        }

        $userIds = [];
        $tokens = [];

        // 1. Match explicit CKEditor mention chips: data-user-id="123" or data-mention-id="123"
        if (preg_match_all('/data-(?:user|mention)-id=["\'](\d+)["\']/i', $raw, $idMatches)) {
            foreach ($idMatches[1] as $idVal) {
                $userIds[] = (int) $idVal;
            }
        }

        // 2. Match explicit data-username="username"
        if (preg_match_all('/data-username=["\']([^"\']+)["\']/i', $raw, $unameMatches)) {
            foreach ($unameMatches[1] as $uVal) {
                $tokens[] = mb_strtolower(trim($uVal));
            }
        }

        // 3. Match @username or @email_prefix in stripped text
        $plain = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match_all('/(?:^|[\s(>])@([a-zA-Z0-9_.\-]{2,60})/u', $plain, $atMatches)) {
            foreach ($atMatches[1] as $handle) {
                $tokens[] = mb_strtolower(trim($handle, '.,;:!?'));
            }
        }

        $userIds = array_values(array_unique(array_filter($userIds)));
        $tokens = array_values(array_unique(array_filter($tokens)));

        if (empty($userIds) && empty($tokens)) {
            return collect();
        }

        $candidates = $project
            ? ($project->relationLoaded('users') ? $project->users : $project->users()->get())
            : collect();

        if ($candidates->isEmpty()) {
            $candidates = User::query()->where('status', 'active')->get();
        }

        $matched = $candidates->filter(function (User $u) use ($userIds, $tokens) {
            if (in_array((int) $u->id, $userIds, true)) {
                return true;
            }
            $uname = mb_strtolower((string) ($u->username ?? ''));
            $emailPrefix = mb_strtolower( strtok((string) $u->email, '@') ?: '');
            $slugName = mb_strtolower(preg_replace('/\s+/', '', (string) ($u->full_name ?? $u->name ?? '')));
            $dottedName = mb_strtolower(preg_replace('/\s+/', '.', (string) ($u->full_name ?? $u->name ?? '')));

            foreach ($tokens as $tk) {
                if ($tk !== '' && ($tk === $uname || $tk === $emailPrefix || $tk === $slugName || $tk === $dottedName)) {
                    return true;
                }
            }
            return false;
        });

        // Also lookup by explicit userIds if any mentioned user wasn't in the initial collection
        if (!empty($userIds)) {
            $missingIds = array_diff($userIds, $matched->pluck('id')->all());
            if (!empty($missingIds)) {
                $extraUsers = User::whereIn('id', $missingIds)->get();
                $matched = $matched->merge($extraUsers);
            }
        }

        return $matched->unique('id')->values();
    }

    /**
     * Dispatch high-priority notifications to any newly @mentioned users in content.
     */
    public static function notifyMentions(
        ?string $content,
        ?Project $project,
        ?User $actor,
        string $subjectLabel,
        ?string $url = null,
        array $extraData = [],
        ?string $previousContent = null,
    ): Collection {
        $mentioned = static::extractMentionedUsers($content, $project);
        if ($mentioned->isEmpty()) {
            return collect();
        }

        if ($previousContent !== null && trim($previousContent) !== '') {
            $previouslyMentionedIds = static::extractMentionedUsers($previousContent, $project)
                ->pluck('id')
                ->all();
            $mentioned = $mentioned->reject(fn (User $u) => in_array($u->id, $previouslyMentionedIds, true));
        }

        $recipients = static::prepareRecipients($mentioned, $actor);
        if ($recipients->isEmpty()) {
            return collect();
        }

        $plainSnippet = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $content), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $snippet = mb_substr($plainSnippet, 0, 130) . (mb_strlen($plainSnippet) > 130 ? '...' : '');
        $actorName = $actor?->full_name ?? $actor?->name ?? 'Một thành viên';

        $data = array_merge($extraData, [
            'priority' => 'urgent',
            'is_mention' => true,
        ]);

        $notification = new TaskPilotNotification(
            self::EVENT_USER_MENTIONED,
            '[Ưu tiên] ' . $actorName . ' đã nhắc đến bạn (@mention)',
            $actorName . ' đã đề cập đến bạn trong ' . $subjectLabel . ': "' . $snippet . '"',
            $data,
            $url,
            $actor,
        );

        static::safeSend($recipients, $notification);

        return $recipients;
    }

    protected static function prepareRecipients(Collection $recipients, ?User $actor): Collection
    {
        if ($actor) {
            $recipients = $recipients->reject(fn (User $u) => $u->id === $actor->id);
        }

        return $recipients;
    }

    protected static function safeSend($recipients, TaskPilotNotification $notification): void
    {
        try {
            Notification::send($recipients, $notification);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification dispatch failed: ' . $e->getMessage());
        }
    }
}
