<?php

namespace ProjectsManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProjectsManager\Comment;
use ProjectsManager\Project;
use ProjectsManager\Task;
use ProjectsManager\User;

class RealtimeAndMentionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Return candidate users for @Mention autocomplete in CKEditor / descriptions / comments.
     */
    public function mentionCandidates(Request $request): JsonResponse
    {
        $projectId = $request->integer('project_id') ?: null;
        $taskId = $request->integer('task_id') ?: null;
        $query = mb_strtolower(trim((string) $request->query('q', '')));

        if (!$projectId && $taskId) {
            $task = Task::find($taskId);
            $projectId = $task?->project_id;
        }

        $users = collect();
        if ($projectId) {
            $project = Project::with('users')->find($projectId);
            if ($project && $project->users->isNotEmpty()) {
                $users = $project->users;
            }
        }

        if ($users->isEmpty()) {
            $users = User::query()->where('status', 'active')->limit(30)->get();
        }

        if ($query !== '') {
            $users = $users->filter(function (User $u) use ($query) {
                $name = mb_strtolower((string) ($u->full_name ?? $u->name ?? ''));
                $uname = mb_strtolower((string) ($u->username ?? ''));
                $email = mb_strtolower((string) ($u->email ?? ''));
                $job = mb_strtolower((string) ($u->job_title ?? ''));

                return str_contains($name, $query)
                    || str_contains($uname, $query)
                    || str_contains($email, $query)
                    || str_contains($job, $query);
            });
        }

        $items = $users->take(12)->values()->map(function (User $u) {
            $displayName = $u->full_name ?: $u->name ?: 'User #' . $u->id;
            $handle = $u->username ?: (strtok((string) $u->email, '@') ?: ('user' . $u->id));

            return [
                'id' => $u->id,
                'name' => $displayName,
                'username' => $handle,
                'email' => $u->email,
                'job_title' => $u->job_title ?: 'Thành viên dự án',
                'initials' => strtoupper(mb_substr($displayName, 0, 2)),
            ];
        });

        return response()->json([
            'candidates' => $items,
        ]);
    }

    /**
     * Return lightweight Kanban board state for real-time synchronization.
     */
    public function kanbanState(Request $request, Project $project): JsonResponse
    {
        $tasks = Task::with(['assignee', 'sprint'])
            ->where('project_id', $project->id)
            ->orderBy('id', 'desc')
            ->get();

        $formatted = $tasks->map(function (Task $t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'issue_key' => $t->issue_key ?? ('TASK-' . $t->id),
                'issue_type' => $t->issue_type ?? 'task',
                'status' => $t->status ?? 'todo',
                'priority' => $t->priority ?? 'medium',
                'story_points' => $t->story_points,
                'labels' => $t->labels,
                'sprint_id' => $t->sprint_id,
                'sprint' => $t->sprint ? ['id' => $t->sprint->id, 'name' => $t->sprint->name] : null,
                'project_id' => $t->project_id,
                'assignee_id' => $t->assignee_id,
                'assignee' => $t->assignee ? [
                    'id' => $t->assignee->id,
                    'name' => $t->assignee->full_name ?? $t->assignee->name,
                ] : null,
                'start_date' => $t->start_date,
                'due_date' => $t->due_date,
                'updated_at_ts' => optional($t->updated_at)->timestamp ?? 0,
            ];
        });

        $fingerprint = md5($formatted->map(fn ($item) => $item['id'] . ':' . $item['status'] . ':' . $item['assignee_id'] . ':' . $item['sprint_id'] . ':' . $item['updated_at_ts'])->implode('|'));

        return response()->json([
            'project_id' => $project->id,
            'fingerprint' => $fingerprint,
            'tasks' => $formatted,
            'timestamp' => time(),
        ]);
    }

    /**
     * Return lightweight Task comments & status state for real-time comment feed synchronization.
     */
    public function taskCommentsState(Request $request, Task $task): JsonResponse
    {
        $comments = Comment::with('user')
            ->where('commentable_type', Task::class)
            ->where('commentable_id', $task->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $comments->map(function (Comment $c) {
            $userName = $c->user ? ($c->user->full_name ?? $c->user->name) : 'Người dùng ẩn danh';
            return [
                'id' => $c->id,
                'body' => (string) $c->body,
                'user_id' => $c->user_id,
                'user_name' => $userName,
                'user_initials' => strtoupper(mb_substr($userName, 0, 2)),
                'created_at_human' => $c->created_at ? $c->created_at->diffForHumans() : 'Vừa xong',
                'updated_at_ts' => optional($c->updated_at)->timestamp ?? 0,
            ];
        });

        $fingerprint = md5(
            $task->status . ':' . $task->assignee_id . ':' . $task->story_points . '|' .
            $formatted->map(fn ($c) => $c['id'] . ':' . $c['updated_at_ts'])->implode(',')
        );

        return response()->json([
            'task_id' => $task->id,
            'status' => $task->status,
            'priority' => $task->priority,
            'story_points' => $task->story_points,
            'assignee_id' => $task->assignee_id,
            'assignee_name' => $task->assignee ? ($task->assignee->full_name ?? $task->assignee->name) : 'Chưa gán',
            'comments_count' => $formatted->count(),
            'comments' => $formatted,
            'fingerprint' => $fingerprint,
            'timestamp' => time(),
        ]);
    }
}

