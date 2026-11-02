<?php

namespace ProjectsManager\Modules\Projects\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Project;
use ProjectsManager\Services\TqlQueryService;
use ProjectsManager\Task;
use ProjectsManager\TqlSavedFilter;
use ProjectsManager\User;

class TqlController extends Controller
{
    public function __construct(private readonly TqlQueryService $tqlService)
    {
    }

    public function index(Request $request, ?Project $project = null): View
    {
        $userId = Auth::id();
        $projectId = $project ? $project->id : ($request->filled('project_id') ? (int) $request->input('project_id') : null);

        // Determine filter mode: 'simple' (default) or 'tql'
        $mode = $request->input('mode', $request->filled('q') ? 'tql' : 'simple');

        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');
        $priority = (string) $request->input('priority', 'all');
        $issueType = (string) $request->input('issue_type', 'all');
        $assignee = (string) $request->input('assignee', 'all');
        $sprintId = (string) $request->input('sprint_id', 'all');

        $q = trim((string) $request->input('q', ''));
        $error = null;
        $clauses = [];
        $tasks = null;

        if ($mode === 'simple') {
            $builder = Task::query()->with(['project', 'assignee', 'reporter', 'sprintRelation']);

            if ($projectId) {
                $builder->where('tasks.project_id', $projectId);
            }

            if ($search !== '') {
                $builder->where(function ($query) use ($search) {
                    $query->where('tasks.name', 'like', "%{$search}%")
                          ->orWhere('tasks.issue_key', 'like', "%{$search}%")
                          ->orWhere('tasks.description', 'like', "%{$search}%");
                });
            }

            if ($status !== '' && $status !== 'all') {
                if ($status === 'open') {
                    $builder->whereNotIn('tasks.status', ['done', 'closed', 'completed', 'resolved']);
                } else {
                    $builder->where('tasks.status', $status);
                }
            }

            if ($priority !== '' && $priority !== 'all') {
                $builder->where('tasks.priority', $priority);
            }

            if ($issueType !== '' && $issueType !== 'all') {
                $builder->where('tasks.issue_type', $issueType);
            }

            if ($assignee !== '' && $assignee !== 'all') {
                if ($assignee === 'me') {
                    $builder->where('tasks.assignee_id', $userId);
                } elseif ($assignee === 'unassigned') {
                    $builder->whereNull('tasks.assignee_id');
                } else {
                    $builder->where('tasks.assignee_id', (int) $assignee);
                }
            }

            if ($sprintId !== '' && $sprintId !== 'all') {
                if ($sprintId === 'none') {
                    $builder->whereNull('tasks.sprint_id');
                } else {
                    $builder->where('tasks.sprint_id', (int) $sprintId);
                }
            }

            $builder->orderByDesc('tasks.id');
            $tasks = $builder->paginate(25)->withQueryString();
        } else {
            // TQL Advanced Mode
            if ($q === '' && ! $request->has('q')) {
                $q = $projectId ? 'status != "done" ORDER BY priority DESC' : 'assignee = currentUser() AND status != "done" ORDER BY priority DESC';
            }

            $parsed = $this->tqlService->parse($q, $projectId);
            if (! $parsed['error'] && $parsed['builder']) {
                $tasks = $parsed['builder']->paginate(25)->withQueryString();
            }
            $error = $parsed['error'];
            $clauses = $parsed['clauses'];
        }

        $savedFilters = TqlSavedFilter::where('user_id', $userId)
            ->orWhere('is_public', true)
            ->latest()
            ->get();

        $allProjects = Project::where('user_id', $userId)
            ->orWhereHas('users', fn ($qu) => $qu->where('users.id', $userId))
            ->get(['id', 'name', 'project_code']);

        $members = $project
            ? $project->users()->get(['users.id', 'users.name'])
            : User::select(['id', 'name'])->limit(50)->get();

        $sprints = $project
            ? $project->sprints()->orderByDesc('id')->get(['id', 'name', 'status'])
            : collect();

        return view('projects.tql.index', [
            'project' => $project,
            'mode' => $mode,
            'q' => $q,
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'issueType' => $issueType,
            'assignee' => $assignee,
            'sprintId' => $sprintId,
            'tasks' => $tasks,
            'error' => $error,
            'clauses' => $clauses,
            'savedFilters' => $savedFilters,
            'allProjects' => $allProjects,
            'members' => $members,
            'sprints' => $sprints,
        ]);
    }

    public function saveFilter(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'query' => ['required', 'string', 'max:1000'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $filter = TqlSavedFilter::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'query' => $validated['query'],
            'project_id' => $validated['project_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã lưu bộ lọc thành công.', 'filter' => $filter]);
        }

        return back()->with('success', 'Đã lưu bộ lọc "' . $filter->name . '" thành công.');
    }

    public function deleteFilter(Request $request, TqlSavedFilter $filter): RedirectResponse|JsonResponse
    {
        if ($filter->user_id !== Auth::id()) {
            abort(403);
        }

        $filter->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã xóa bộ lọc đã lưu.']);
        }

        return back()->with('success', 'Đã xóa bộ lọc thành công.');
    }

    public function export(Request $request, ?Project $project = null): Response
    {
        $mode = $request->input('mode', $request->filled('q') ? 'tql' : 'simple');
        $projectId = $project ? $project->id : null;

        if ($mode === 'simple') {
            $builder = Task::query()->with(['project', 'assignee', 'reporter']);
            if ($projectId) {
                $builder->where('tasks.project_id', $projectId);
            }
            if ($request->filled('search')) {
                $s = trim((string) $request->input('search'));
                $builder->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('issue_key', 'like', "%{$s}%"));
            }
            if ($request->filled('status') && $request->input('status') !== 'all') {
                $builder->where('status', $request->input('status'));
            }
            if ($request->filled('priority') && $request->input('priority') !== 'all') {
                $builder->where('priority', $request->input('priority'));
            }
            if ($request->filled('issue_type') && $request->input('issue_type') !== 'all') {
                $builder->where('issue_type', $request->input('issue_type'));
            }
            $tasks = $builder->limit(500)->get();
        } else {
            $q = trim((string) $request->input('q', ''));
            $parsed = $this->tqlService->parse($q, $projectId);
            $tasks = $parsed['builder'] ? $parsed['builder']->limit(500)->get() : collect();
        }

        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        $csv .= "Key,Tên công việc,Loại,Trạng thái,Độ ưu tiên,Dự án,Người phụ trách,Story Points,Hạn chót\n";

        foreach ($tasks as $t) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $t->issue_key ?? 'TP-' . $t->id,
                str_replace('"', '""', $t->name),
                strtoupper($t->issue_type ?? 'TASK'),
                strtoupper($t->status),
                strtoupper($t->priority),
                str_replace('"', '""', $t->project->name ?? ''),
                str_replace('"', '""', $t->assignee->name ?? 'Unassigned'),
                $t->story_points ?? '0',
                $t->due_date ? $t->due_date->format('d/m/Y') : ''
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tasks-export-' . date('Ymd-His') . '.csv"',
        ]);
    }
}
