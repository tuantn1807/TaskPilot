<?php

namespace ProjectsManager\Modules\Projects\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Project;
use ProjectsManager\ProjectActivity;
use ProjectsManager\ProjectDevelopmentLink;
use ProjectsManager\Task;

class ProjectDevelopmentController extends Controller
{
    public function index(Project $project): View
    {
        $project->load([
            'developmentLinks' => fn ($q) => $q->latest('linked_at')->latest('id'),
            'developmentLinks.user',
            'developmentLinks.task',
        ]);

        $allTasks = $project->tasks()
            ->with(['assignee', 'developmentLinks'])
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 1 WHEN status = 'review' THEN 2 WHEN status = 'todo' THEN 3 ELSE 4 END")
            ->orderByDesc('id')
            ->get();

        $activeTasks = $allTasks->filter(fn ($t) => !in_array($t->status, ['done', 'completed', 'closed', 'cancelled'], true))->values();

        $links = $project->developmentLinks;
        $linksByType = $links->groupBy('type');

        $detectedRepoUrl = $this->resolveRepoUrl($project);
        $repoShortName = $this->extractRepoShortName($detectedRepoUrl);
        $hasGithubToken = !empty($this->resolveGithubToken($project));
        $webhookUrl = url('/webhooks/github/' . $project->id);

        $stats = [
            'total'        => $links->count(),
            'branches'     => $links->where('type', 'branch')->count(),
            'commits'      => $links->where('type', 'commit')->count(),
            'prs'          => $links->where('type', 'pr')->count(),
            'prs_open'     => $links->where('type', 'pr')->where('status', 'open')->count(),
            'prs_merged'   => $links->where('type', 'pr')->where('status', 'merged')->count(),
            'pipelines'    => $links->whereIn('type', ['pipeline', 'deployment'])->count(),
            'linked_tasks' => $links->whereNotNull('task_id')->pluck('task_id')->unique()->count(),
        ];

        $branchLinks = $links->where('type', 'branch')->values();

        return view('projects.development.index', compact(
            'project',
            'links',
            'linksByType',
            'branchLinks',
            'allTasks',
            'activeTasks',
            'stats',
            'detectedRepoUrl',
            'repoShortName',
            'hasGithubToken',
            'webhookUrl'
        ));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'type'           => ['required', 'string', 'in:branch,commit,pr,pipeline,deployment,repo'],
            'label'          => ['required', 'string', 'max:255'],
            'url'            => ['nullable', 'string', 'max:2048'],
            'status'         => ['nullable', 'string', 'max:50'],
            'task_id'        => ['nullable', 'integer'],
            'branch_name'    => ['nullable', 'string', 'max:255'],
            'commit_sha'     => ['nullable', 'string', 'max:64'],
            'author_name'    => ['nullable', 'string', 'max:255'],
            'repo_name'      => ['nullable', 'string', 'max:255'],
            'auto_move_task' => ['nullable', 'boolean'],
        ]);

        $repoBaseUrl = rtrim($this->resolveRepoUrl($project), '/');
        $repoShort = $data['repo_name'] ?? $this->extractRepoShortName($repoBaseUrl);

        $url = trim((string) ($data['url'] ?? ''));
        $branchName = trim((string) ($data['branch_name'] ?? ''));
        $commitSha = trim((string) ($data['commit_sha'] ?? ''));

        if ($data['type'] === 'branch' && $branchName === '') {
            $branchName = $data['label'];
        }

        if ($url === '') {
            if ($data['type'] === 'branch' && $branchName !== '') {
                $url = $repoBaseUrl . '/tree/' . rawurlencode($branchName);
            } elseif ($data['type'] === 'commit' && $commitSha !== '') {
                $url = $repoBaseUrl . '/commit/' . rawurlencode($commitSha);
            } elseif ($data['type'] === 'pr') {
                $url = $repoBaseUrl . '/pulls';
            } elseif ($data['type'] === 'pipeline') {
                $url = $repoBaseUrl . '/actions';
            } else {
                $url = $repoBaseUrl;
            }
        }

        $taskId = !empty($data['task_id']) ? (int) $data['task_id'] : null;
        if (!$taskId) {
            $taskId = $this->detectTaskIdFromText(
                $project,
                implode(' ', [$data['label'], $branchName, $url])
            );
        } else {
            $validTask = Task::where('project_id', $project->id)->where('id', $taskId)->exists();
            if (!$validTask) {
                $taskId = null;
            }
        }

        $defaultStatus = match ($data['type']) {
            'branch'     => 'active',
            'commit'     => 'committed',
            'pr'         => 'open',
            'pipeline'   => 'success',
            'deployment' => 'success',
            default      => 'active',
        };

        $payload = [
            'user_id'   => Auth::id(),
            'type'      => $data['type'],
            'label'     => $data['label'],
            'url'       => $url,
            'status'    => !empty($data['status']) ? $data['status'] : $defaultStatus,
            'linked_at' => now(),
        ];

        if (Schema::hasColumn('project_development_links', 'task_id')) {
            $payload['task_id'] = $taskId;
        }
        if (Schema::hasColumn('project_development_links', 'branch_name')) {
            $payload['branch_name'] = $branchName ?: null;
        }
        if (Schema::hasColumn('project_development_links', 'commit_sha')) {
            $payload['commit_sha'] = $commitSha ?: null;
        }
        if (Schema::hasColumn('project_development_links', 'author_name')) {
            $payload['author_name'] = !empty($data['author_name'])
                ? $data['author_name']
                : (Auth::user()->full_name ?? Auth::user()->name ?? 'Developer');
        }
        if (Schema::hasColumn('project_development_links', 'repo_name')) {
            $payload['repo_name'] = $repoShort ?: null;
        }

        $link = $project->developmentLinks()->create($payload);

        if (!empty($data['auto_move_task']) && $taskId) {
            $this->autoTransitionTaskStatus($project, $taskId, 'in_progress');
        }

        $this->logGitActivity($project, 'git_' . $data['type'], 'Đã thêm ' . strtoupper($data['type']) . ' Git: ' . $link->label);

        return back()->with('success', 'Đã thêm liên kết phát triển Git thành công.');
    }

    public function updateRepo(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'git_repo_url'        => ['required', 'url', 'max:500'],
            'git_default_branch'  => ['nullable', 'string', 'max:100'],
            'git_provider'        => ['nullable', 'string', 'in:github,gitlab,bitbucket,local'],
            'git_token'           => ['nullable', 'string', 'max:500'],
            'clear_git_token'     => ['nullable', 'boolean'],
            'git_webhook_secret'  => ['nullable', 'string', 'max:191'],
            'git_auto_transition' => ['nullable', 'boolean'],
        ]);

        $updates = [];
        if (Schema::hasColumn('projects', 'git_repo_url')) {
            $updates['git_repo_url'] = rtrim(preg_replace('/\.git$/i', '', trim($data['git_repo_url'])), '/');
        }
        if (Schema::hasColumn('projects', 'git_default_branch')) {
            $updates['git_default_branch'] = trim($data['git_default_branch'] ?: 'develop');
        }
        if (Schema::hasColumn('projects', 'git_provider')) {
            $updates['git_provider'] = $data['git_provider'] ?: 'github';
        }
        if (Schema::hasColumn('projects', 'git_token')) {
            if (!empty($data['clear_git_token'])) {
                $updates['git_token'] = null;
            } elseif (!empty($data['git_token']) && !str_contains($data['git_token'], '••••')) {
                $updates['git_token'] = trim($data['git_token']);
            }
        }
        if (Schema::hasColumn('projects', 'git_webhook_secret') && array_key_exists('git_webhook_secret', $data)) {
            $updates['git_webhook_secret'] = trim((string) $data['git_webhook_secret']) ?: null;
        }
        if (Schema::hasColumn('projects', 'git_auto_transition')) {
            $updates['git_auto_transition'] = $request->boolean('git_auto_transition');
        }

        if (!empty($updates)) {
            $project->update($updates);
        }

        return back()->with('success', 'Đã lưu cấu hình tích hợp GitHub cho dự án.');
    }

    public function createGithubBranch(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'branch_name'     => ['required', 'string', 'max:255'],
            'base_branch'     => ['nullable', 'string', 'max:100'],
            'task_id'         => ['nullable', 'integer'],
            'auto_move_task'  => ['nullable', 'boolean'],
            'push_to_github'  => ['nullable', 'boolean'],
        ]);

        $branchName = trim($data['branch_name']);
        $baseBranch = trim($data['base_branch'] ?: ($project->git_default_branch ?: 'develop'));
        $repoUrl = $this->resolveRepoUrl($project);
        $ownerRepo = $this->extractRepoShortName($repoUrl);
        $token = $this->resolveGithubToken($project);

        $createdOnGithub = false;
        $createdSha = null;
        $githubNote = '';

        // 1. If push_to_github is requested and token is present, create branch directly on GitHub REST API
        if ($request->boolean('push_to_github', true) && !empty($token) && preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $ownerRepo)) {
            try {
                $headers = $this->buildGithubHeaders($project);
                $refRes = Http::withHeaders($headers)->timeout(6)
                    ->get("https://api.github.com/repos/{$ownerRepo}/git/ref/heads/" . rawurlencode($baseBranch));

                if (!$refRes->successful() && $baseBranch !== 'main') {
                    $refRes = Http::withHeaders($headers)->timeout(6)
                        ->get("https://api.github.com/repos/{$ownerRepo}/git/ref/heads/main");
                }

                if ($refRes->successful()) {
                    $baseSha = $refRes->json('object.sha');
                    if ($baseSha) {
                        $createRes = Http::withHeaders($headers)->timeout(6)
                            ->post("https://api.github.com/repos/{$ownerRepo}/git/refs", [
                                'ref' => 'refs/heads/' . $branchName,
                                'sha' => $baseSha,
                            ]);

                        if ($createRes->successful() || $createRes->status() === 422) {
                            $createdOnGithub = true;
                            $createdSha = substr($baseSha, 0, 7);
                            $githubNote = ' (Đã tạo trực tiếp trên GitHub)';
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to recording branch link
            }
        }

        $taskId = !empty($data['task_id']) ? (int) $data['task_id'] : $this->detectTaskIdFromText($project, $branchName);

        $this->upsertGitItem($project, [
            'type'        => 'branch',
            'label'       => $branchName,
            'branch_name' => $branchName,
            'commit_sha'  => $createdSha,
            'author_name' => Auth::user()->full_name ?? Auth::user()->name ?? 'Developer',
            'repo_name'   => $ownerRepo,
            'url'         => rtrim($repoUrl, '/') . '/tree/' . rawurlencode($branchName),
            'status'      => 'active',
            'linked_at'   => now(),
            'task_id'     => $taskId,
        ]);

        if ($request->boolean('auto_move_task', true) && $taskId) {
            $this->autoTransitionTaskStatus($project, $taskId, 'in_progress');
        }

        $this->logGitActivity($project, 'github_branch', "Đã khởi tạo nhánh Git: {$branchName}{$githubNote}");

        return back()->with('success', "Đã tạo và ghi nhận nhánh '{$branchName}'{$githubNote}.");
    }

    public function createGithubPullRequest(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'head'    => ['required', 'string', 'max:255'],
            'base'    => ['required', 'string', 'max:100'],
            'body'    => ['nullable', 'string', 'max:5000'],
            'task_id' => ['nullable', 'integer'],
        ]);

        $repoUrl = rtrim($this->resolveRepoUrl($project), '/');
        $ownerRepo = $this->extractRepoShortName($repoUrl);
        $token = $this->resolveGithubToken($project);

        $prUrl = "{$repoUrl}/compare/" . rawurlencode($data['base']) . '...' . rawurlencode($data['head']) . '?expand=1';
        $prNumberLabel = $data['title'];
        $prSha = null;
        $createdViaApi = false;

        if (!empty($token) && preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $ownerRepo)) {
            try {
                $res = Http::withHeaders($this->buildGithubHeaders($project))
                    ->timeout(8)
                    ->post("https://api.github.com/repos/{$ownerRepo}/pulls", [
                        'title' => $data['title'],
                        'head'  => $data['head'],
                        'base'  => $data['base'],
                        'body'  => $data['body'] ?? '',
                    ]);

                if ($res->successful()) {
                    $prData = $res->json();
                    $num = $prData['number'] ?? null;
                    $prUrl = $prData['html_url'] ?? $prUrl;
                    if ($num) {
                        $prNumberLabel = "#{$num} " . $data['title'];
                        $prSha = '#' . $num;
                    }
                    $createdViaApi = true;
                }
            } catch (\Throwable $e) {
                // Fallback to compare URL
            }
        }

        $taskId = !empty($data['task_id'])
            ? (int) $data['task_id']
            : $this->detectTaskIdFromText($project, $data['title'] . ' ' . $data['head']);

        $this->upsertGitItem($project, [
            'type'        => 'pr',
            'label'       => $prNumberLabel,
            'branch_name' => $data['head'],
            'commit_sha'  => $prSha,
            'author_name' => Auth::user()->full_name ?? Auth::user()->name ?? 'Developer',
            'repo_name'   => $ownerRepo,
            'url'         => $prUrl,
            'status'      => 'open',
            'linked_at'   => now(),
            'task_id'     => $taskId,
        ]);

        if ($taskId && ($project->git_auto_transition ?? true)) {
            $this->autoTransitionTaskStatus($project, $taskId, 'review');
        }

        $this->logGitActivity($project, 'github_pr', "Đã tạo Pull Request: {$prNumberLabel}");

        $msg = $createdViaApi
            ? "Đã tạo Pull Request '{$prNumberLabel}' trực tiếp trên GitHub thành công!"
            : "Đã ghi nhận Pull Request '{$prNumberLabel}'. Bạn có thể bấm vào liên kết để mở trang tạo PR trên GitHub.";

        return back()->with('success', $msg);
    }

    public function checkGithubConnection(Project $project): JsonResponse
    {
        $repoUrl = $this->resolveRepoUrl($project);
        $ownerRepo = $this->extractRepoShortName($repoUrl);
        $token = $this->resolveGithubToken($project);

        if (!preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $ownerRepo)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Định dạng GitHub Repository không hợp lệ: ' . $ownerRepo,
            ], 422);
        }

        try {
            $res = Http::withHeaders($this->buildGithubHeaders($project))
                ->timeout(6)
                ->get("https://api.github.com/repos/{$ownerRepo}");

            $rateRemaining = $res->header('X-RateLimit-Remaining');
            $rateLimit = $res->header('X-RateLimit-Limit');

            if ($res->successful()) {
                $info = $res->json();
                return response()->json([
                    'ok'             => true,
                    'authenticated'  => !empty($token),
                    'full_name'      => $info['full_name'] ?? $ownerRepo,
                    'private'        => (bool) ($info['private'] ?? false),
                    'default_branch' => $info['default_branch'] ?? 'main',
                    'stars'          => (int) ($info['stargazers_count'] ?? 0),
                    'forks'          => (int) ($info['forks_count'] ?? 0),
                    'open_issues'    => (int) ($info['open_issues_count'] ?? 0),
                    'rate_remaining' => $rateRemaining,
                    'rate_limit'     => $rateLimit,
                    'html_url'       => $info['html_url'] ?? $repoUrl,
                    'message'        => 'Kết nối GitHub API thành công (' . (!empty($token) ? 'Có xác thực Token' : 'Chế độ Public API') . ').',
                ]);
            }

            return response()->json([
                'ok'      => false,
                'status'  => $res->status(),
                'message' => $res->status() === 404
                    ? "Không tìm thấy repo '{$ownerRepo}' hoặc đây là Private Repo cần cấu hình GitHub Personal Access Token."
                    : 'GitHub API trả về mã lỗi HTTP ' . $res->status(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Không thể kết nối tới GitHub API: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function syncGit(Request $request, Project $project): RedirectResponse
    {
        $repoUrl = $this->resolveRepoUrl($project);
        $repoShort = $this->extractRepoShortName($repoUrl);

        $syncedBranches = 0;
        $syncedCommits = 0;
        $syncedPrs = 0;
        $syncedPipelines = 0;

        // 1. Sync from GitHub REST API (Branches, Pull Requests, Commits, GitHub Actions Workflows)
        if (preg_match('#github\.com/([^/]+/[^/]+)#i', $repoUrl, $m)) {
            $ownerRepo = preg_replace('/\.git$/i', '', $m[1]);
            try {
                $headers = $this->buildGithubHeaders($project);

                // 1a. Branches
                $branchesRes = Http::withHeaders($headers)->timeout(6)
                    ->get("https://api.github.com/repos/{$ownerRepo}/branches?per_page=30");
                if ($branchesRes->successful() && is_array($branchesRes->json())) {
                    foreach ($branchesRes->json() as $b) {
                        $bName = $b['name'] ?? null;
                        $sha = isset($b['commit']['sha']) ? substr($b['commit']['sha'], 0, 7) : null;
                        if (!$bName) {
                            continue;
                        }
                        if ($this->upsertGitItem($project, [
                            'type'        => 'branch',
                            'label'       => $bName,
                            'branch_name' => $bName,
                            'commit_sha'  => $sha,
                            'author_name' => 'GitHub',
                            'repo_name'   => $ownerRepo,
                            'url'         => "https://github.com/{$ownerRepo}/tree/" . rawurlencode($bName),
                            'status'      => in_array($bName, ['main', 'master', 'develop'], true) ? 'default' : 'active',
                            'linked_at'   => now(),
                        ])) {
                            $syncedBranches++;
                        }
                    }
                }

                // 1b. Pull Requests
                $prsRes = Http::withHeaders($headers)->timeout(6)
                    ->get("https://api.github.com/repos/{$ownerRepo}/pulls?state=all&per_page=20");
                if ($prsRes->successful() && is_array($prsRes->json())) {
                    foreach ($prsRes->json() as $pr) {
                        $prNumber = $pr['number'] ?? null;
                        $prTitle = $pr['title'] ?? '';
                        if (!$prNumber) {
                            continue;
                        }
                        $status = !empty($pr['merged_at']) ? 'merged' : ($pr['state'] ?? 'open');
                        $headBranch = $pr['head']['ref'] ?? null;
                        $author = $pr['user']['login'] ?? 'GitHub';
                        $createdAt = !empty($pr['created_at']) ? Carbon::parse($pr['created_at']) : now();

                        if ($this->upsertGitItem($project, [
                            'type'        => 'pr',
                            'label'       => "#{$prNumber} {$prTitle}",
                            'branch_name' => $headBranch,
                            'commit_sha'  => '#' . $prNumber,
                            'author_name' => $author,
                            'repo_name'   => $ownerRepo,
                            'url'         => $pr['html_url'] ?? "https://github.com/{$ownerRepo}/pull/{$prNumber}",
                            'status'      => $status,
                            'linked_at'   => $createdAt,
                        ])) {
                            $syncedPrs++;
                        }
                    }
                }

                // 1c. Commits
                $commitsRes = Http::withHeaders($headers)->timeout(6)
                    ->get("https://api.github.com/repos/{$ownerRepo}/commits?per_page=25");
                if ($commitsRes->successful() && is_array($commitsRes->json())) {
                    foreach ($commitsRes->json() as $c) {
                        $fullSha = $c['sha'] ?? '';
                        $shortSha = substr($fullSha, 0, 7);
                        $rawMsg = trim((string) ($c['commit']['message'] ?? ''));
                        $firstLine = strtok($rawMsg, "\r\n") ?: $shortSha;
                        $author = $c['commit']['author']['name'] ?? ($c['author']['login'] ?? 'Developer');
                        $commitDate = !empty($c['commit']['author']['date'])
                            ? Carbon::parse($c['commit']['author']['date'])
                            : now();

                        if (!$shortSha) {
                            continue;
                        }

                        if ($this->upsertGitItem($project, [
                            'type'        => 'commit',
                            'label'       => Str::limit($firstLine, 180),
                            'branch_name' => $project->git_default_branch ?: 'develop',
                            'commit_sha'  => $shortSha,
                            'author_name' => $author,
                            'repo_name'   => $ownerRepo,
                            'url'         => $c['html_url'] ?? "https://github.com/{$ownerRepo}/commit/{$fullSha}",
                            'status'      => 'committed',
                            'linked_at'   => $commitDate,
                        ])) {
                            $syncedCommits++;
                        }
                    }
                }

                // 1d. GitHub Actions Workflow Runs (CI/CD Pipelines)
                $actionsRes = Http::withHeaders($headers)->timeout(5)
                    ->get("https://api.github.com/repos/{$ownerRepo}/actions/runs?per_page=10");
                if ($actionsRes->successful() && is_array($actionsRes->json('workflow_runs'))) {
                    foreach ($actionsRes->json('workflow_runs') as $run) {
                        $runId = $run['id'] ?? null;
                        $runName = $run['display_title'] ?? ($run['name'] ?? 'GitHub Actions Run');
                        $headBranch = $run['head_branch'] ?? null;
                        $headSha = isset($run['head_sha']) ? substr($run['head_sha'], 0, 7) : null;
                        $conclusion = $run['conclusion'] ?? ($run['status'] ?? 'success');
                        $pipeStatus = match ($conclusion) {
                            'success'            => 'success',
                            'failure', 'timed_out', 'cancelled' => 'failed',
                            default              => 'running',
                        };
                        $runDate = !empty($run['created_at']) ? Carbon::parse($run['created_at']) : now();

                        if ($runId && $this->upsertGitItem($project, [
                            'type'        => 'pipeline',
                            'label'       => "[Actions] {$runName}",
                            'branch_name' => $headBranch,
                            'commit_sha'  => $headSha,
                            'author_name' => $run['actor']['login'] ?? 'GitHub Actions',
                            'repo_name'   => $ownerRepo,
                            'url'         => $run['html_url'] ?? "https://github.com/{$ownerRepo}/actions/runs/{$runId}",
                            'status'      => $pipeStatus,
                            'linked_at'   => $runDate,
                        ])) {
                            $syncedPipelines++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to local git below
            }
        }

        // 2. Also sync from Local Git Repository if available
        $basePath = base_path();
        if (is_dir($basePath . '/.git')) {
            $branchOutput = @shell_exec('git -C ' . escapeshellarg($basePath) . ' for-each-ref --sort=-committerdate refs/heads/ refs/remotes/origin/ --format="%(refname:short)|%(objectname:short)|%(authorname)|%(committerdate:iso8601)" 2>/dev/null');
            if (is_string($branchOutput) && trim($branchOutput) !== '') {
                $seenBranches = [];
                foreach (explode("\n", trim($branchOutput)) as $line) {
                    $parts = explode('|', trim($line));
                    if (count($parts) < 2) {
                        continue;
                    }
                    $rawBranch = trim($parts[0]);
                    if ($rawBranch === 'origin/HEAD' || $rawBranch === 'origin') {
                        continue;
                    }
                    $cleanBranch = preg_replace('#^origin/#', '', $rawBranch);
                    if (isset($seenBranches[$cleanBranch])) {
                        continue;
                    }
                    $seenBranches[$cleanBranch] = true;

                    $sha = trim($parts[1] ?? '');
                    $author = trim($parts[2] ?? 'Developer');
                    $date = !empty($parts[3]) ? Carbon::parse($parts[3]) : now();

                    if ($this->upsertGitItem($project, [
                        'type'        => 'branch',
                        'label'       => $cleanBranch,
                        'branch_name' => $cleanBranch,
                        'commit_sha'  => $sha,
                        'author_name' => $author,
                        'repo_name'   => $repoShort,
                        'url'         => rtrim($repoUrl, '/') . '/tree/' . rawurlencode($cleanBranch),
                        'status'      => in_array($cleanBranch, ['main', 'master', 'develop'], true) ? 'default' : 'active',
                        'linked_at'   => $date,
                    ])) {
                        $syncedBranches++;
                    }
                }
            }

            $logOutput = @shell_exec('git -C ' . escapeshellarg($basePath) . ' log -n 25 --pretty=format:"%h|%H|%an|%aI|%s" 2>/dev/null');
            if (is_string($logOutput) && trim($logOutput) !== '') {
                foreach (explode("\n", trim($logOutput)) as $line) {
                    $parts = explode('|', trim($line), 5);
                    if (count($parts) < 5) {
                        continue;
                    }
                    [$shortSha, $fullSha, $author, $isoDate, $subject] = $parts;
                    $commitDate = !empty($isoDate) ? Carbon::parse($isoDate) : now();

                    if (preg_match('/Merge pull request #(\d+) from [^\s\/]+\/(.+)$/i', $subject, $prMatch)) {
                        $prNum = $prMatch[1];
                        $prBranch = trim($prMatch[2]);
                        if ($this->upsertGitItem($project, [
                            'type'        => 'pr',
                            'label'       => "#{$prNum} Merge branch {$prBranch}",
                            'branch_name' => $prBranch,
                            'commit_sha'  => '#' . $prNum,
                            'author_name' => $author,
                            'repo_name'   => $repoShort,
                            'url'         => rtrim($repoUrl, '/') . '/pull/' . $prNum,
                            'status'      => 'merged',
                            'linked_at'   => $commitDate,
                        ])) {
                            $syncedPrs++;
                        }
                    }

                    if ($this->upsertGitItem($project, [
                        'type'        => 'commit',
                        'label'       => Str::limit(trim($subject), 180),
                        'branch_name' => $project->git_default_branch ?: 'develop',
                        'commit_sha'  => trim($shortSha),
                        'author_name' => trim($author),
                        'repo_name'   => $repoShort,
                        'url'         => rtrim($repoUrl, '/') . '/commit/' . trim($fullSha),
                        'status'      => 'committed',
                        'linked_at'   => $commitDate,
                    ])) {
                        $syncedCommits++;
                    }
                }
            }
        }

        if (empty($project->git_repo_url) && Schema::hasColumn('projects', 'git_repo_url')) {
            $project->update([
                'git_repo_url'       => $repoUrl,
                'git_default_branch' => $project->git_default_branch ?: 'develop',
                'git_provider'       => $project->git_provider ?: 'github',
            ]);
        }

        return back()->with(
            'success',
            "Đã đồng bộ GitHub ({$repoShort}): +{$syncedBranches} nhánh, +{$syncedCommits} commits, +{$syncedPrs} Pull Requests, +{$syncedPipelines} Actions."
        );
    }

    public function handleGithubWebhook(Request $request, Project $project): JsonResponse
    {
        $secret = $project->git_webhook_secret ?: config('services.github.webhook_secret');
        if (!empty($secret)) {
            $signature = (string) $request->header('X-Hub-Signature-256', '');
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
            if (!hash_equals($expected, $signature)) {
                return response()->json(['ok' => false, 'message' => 'Invalid webhook signature'], 401);
            }
        }

        $event = (string) $request->header('X-GitHub-Event', 'ping');
        $payload = $request->all();
        $repoFullName = $payload['repository']['full_name'] ?? $this->extractRepoShortName($this->resolveRepoUrl($project));
        $repoHtmlUrl = rtrim($payload['repository']['html_url'] ?? $this->resolveRepoUrl($project), '/');
        $autoMove = (bool) ($project->git_auto_transition ?? true);

        if ($event === 'create' && ($payload['ref_type'] ?? '') === 'branch') {
            $branchName = (string) ($payload['ref'] ?? '');
            $sender = $payload['sender']['login'] ?? 'GitHub';
            if ($branchName !== '') {
                $taskId = $this->detectTaskIdFromText($project, $branchName);
                $this->upsertGitItem($project, [
                    'type'        => 'branch',
                    'label'       => $branchName,
                    'branch_name' => $branchName,
                    'author_name' => $sender,
                    'repo_name'   => $repoFullName,
                    'url'         => "{$repoHtmlUrl}/tree/" . rawurlencode($branchName),
                    'status'      => 'active',
                    'linked_at'   => now(),
                    'task_id'     => $taskId,
                ]);
                if ($autoMove && $taskId) {
                    $this->autoTransitionTaskStatus($project, $taskId, 'in_progress');
                }
            }
        } elseif ($event === 'push') {
            $ref = (string) ($payload['ref'] ?? '');
            $branchName = preg_replace('#^refs/heads/#', '', $ref);
            foreach (($payload['commits'] ?? []) as $c) {
                $fullSha = (string) ($c['id'] ?? '');
                $shortSha = substr($fullSha, 0, 7);
                $msg = strtok(trim((string) ($c['message'] ?? '')), "\r\n") ?: $shortSha;
                $author = $c['author']['name'] ?? ($payload['pusher']['name'] ?? 'Developer');
                $taskId = $this->detectTaskIdFromText($project, $msg . ' ' . $branchName);

                $this->upsertGitItem($project, [
                    'type'        => 'commit',
                    'label'       => Str::limit($msg, 180),
                    'branch_name' => $branchName,
                    'commit_sha'  => $shortSha,
                    'author_name' => $author,
                    'repo_name'   => $repoFullName,
                    'url'         => $c['url'] ?? "{$repoHtmlUrl}/commit/{$fullSha}",
                    'status'      => 'committed',
                    'linked_at'   => now(),
                    'task_id'     => $taskId,
                ]);
                if ($autoMove && $taskId) {
                    $this->autoTransitionTaskStatus($project, $taskId, 'in_progress');
                }
            }
        } elseif ($event === 'pull_request') {
            $pr = $payload['pull_request'] ?? [];
            $prNum = $pr['number'] ?? null;
            $prTitle = $pr['title'] ?? '';
            $headBranch = $pr['head']['ref'] ?? '';
            $isMerged = !empty($pr['merged']);
            $state = $isMerged ? 'merged' : ($pr['state'] ?? 'open');
            $author = $pr['user']['login'] ?? 'GitHub';

            if ($prNum) {
                $taskId = $this->detectTaskIdFromText($project, "{$prTitle} {$headBranch}");
                $this->upsertGitItem($project, [
                    'type'        => 'pr',
                    'label'       => "#{$prNum} {$prTitle}",
                    'branch_name' => $headBranch,
                    'commit_sha'  => '#' . $prNum,
                    'author_name' => $author,
                    'repo_name'   => $repoFullName,
                    'url'         => $pr['html_url'] ?? "{$repoHtmlUrl}/pull/{$prNum}",
                    'status'      => $state,
                    'linked_at'   => now(),
                    'task_id'     => $taskId,
                ]);

                if ($autoMove && $taskId) {
                    if ($isMerged) {
                        $this->autoTransitionTaskStatus($project, $taskId, 'done');
                    } elseif ($state === 'open') {
                        $this->autoTransitionTaskStatus($project, $taskId, 'review');
                    }
                }
            }
        } elseif ($event === 'workflow_run') {
            $run = $payload['workflow_run'] ?? [];
            $runId = $run['id'] ?? null;
            $runName = $run['display_title'] ?? ($run['name'] ?? 'GitHub Actions');
            $headBranch = $run['head_branch'] ?? '';
            $conclusion = $run['conclusion'] ?? ($run['status'] ?? 'running');
            $status = match ($conclusion) {
                'success' => 'success',
                'failure', 'cancelled', 'timed_out' => 'failed',
                default => 'running',
            };
            if ($runId) {
                $this->upsertGitItem($project, [
                    'type'        => 'pipeline',
                    'label'       => "[Actions] {$runName}",
                    'branch_name' => $headBranch,
                    'commit_sha'  => isset($run['head_sha']) ? substr($run['head_sha'], 0, 7) : null,
                    'author_name' => $run['actor']['login'] ?? 'GitHub Actions',
                    'repo_name'   => $repoFullName,
                    'url'         => $run['html_url'] ?? "{$repoHtmlUrl}/actions/runs/{$runId}",
                    'status'      => $status,
                    'linked_at'   => now(),
                ]);
            }
        }

        return response()->json(['ok' => true, 'event' => $event]);
    }

    public function updateStatus(Request $request, Project $project, ProjectDevelopmentLink $link): RedirectResponse
    {
        abort_unless((int) $link->project_id === (int) $project->id, 404);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:active,default,open,merged,closed,committed,running,success,failed'],
        ]);

        $link->update(['status' => $data['status']]);

        // If a PR linked to a task is marked as merged, auto-complete the task if enabled
        if ($link->type === 'pr' && $data['status'] === 'merged' && !empty($link->task_id) && ($project->git_auto_transition ?? true)) {
            $this->autoTransitionTaskStatus($project, (int) $link->task_id, 'done');
        }

        return back()->with('success', 'Đã cập nhật trạng thái liên kết Git.');
    }

    public function destroy(Project $project, ProjectDevelopmentLink $link): RedirectResponse
    {
        abort_unless((int) $link->project_id === (int) $project->id, 404);
        $link->delete();

        return back()->with('success', 'Đã xóa liên kết phát triển.');
    }

    protected function resolveGithubToken(Project $project): ?string
    {
        if (!empty($project->git_token)) {
            return trim((string) $project->git_token);
        }

        $envToken = config('services.github.token') ?: env('GITHUB_TOKEN');
        if (!empty($envToken)) {
            return trim((string) $envToken);
        }

        return null;
    }

    protected function buildGithubHeaders(Project $project): array
    {
        $headers = [
            'Accept'               => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent'           => 'TaskPilot-GitHub-Integration',
        ];

        $token = $this->resolveGithubToken($project);
        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    protected function autoTransitionTaskStatus(Project $project, int $taskId, string $targetType): void
    {
        $task = Task::where('project_id', $project->id)->find($taskId);
        if (!$task) {
            return;
        }

        $activeStatuses = $project->getActiveStatuses();
        $statusKeys = array_column($activeStatuses, 'key');

        if ($targetType === 'in_progress') {
            if ($task->status === 'todo' && in_array('in_progress', $statusKeys, true)) {
                $task->update(['status' => 'in_progress']);
            }
        } elseif ($targetType === 'review') {
            if (in_array($task->status, ['todo', 'in_progress'], true)) {
                if (in_array('review', $statusKeys, true)) {
                    $task->update(['status' => 'review']);
                } elseif (in_array('testing', $statusKeys, true)) {
                    $task->update(['status' => 'testing']);
                } elseif ($task->status === 'todo' && in_array('in_progress', $statusKeys, true)) {
                    $task->update(['status' => 'in_progress']);
                }
            }
        } elseif ($targetType === 'done') {
            if ($task->status !== 'done' && in_array('done', $statusKeys, true)) {
                $task->update(['status' => 'done']);
            }
        }
    }

    protected function logGitActivity(Project $project, string $action, string $description): void
    {
        if (class_exists(ProjectActivity::class) && Schema::hasTable('project_activities')) {
            try {
                ProjectActivity::create([
                    'project_id'  => $project->id,
                    'user_id'     => Auth::id(),
                    'action'      => $action,
                    'description' => $description,
                ]);
            } catch (\Throwable $e) {
                // Ignore activity logging errors
            }
        }
    }

    protected function upsertGitItem(Project $project, array $attrs): bool
    {
        $type = $attrs['type'];
        $query = ProjectDevelopmentLink::where('project_id', $project->id)->where('type', $type);

        if ($type === 'branch') {
            $query->where('label', $attrs['label']);
        } elseif ($type === 'commit' && !empty($attrs['commit_sha'])) {
            if (Schema::hasColumn('project_development_links', 'commit_sha')) {
                $query->where('commit_sha', $attrs['commit_sha']);
            } else {
                $query->where('url', $attrs['url']);
            }
        } else {
            $query->where('url', $attrs['url']);
        }

        $existing = $query->first();

        $taskId = $attrs['task_id'] ?? $this->detectTaskIdFromText(
            $project,
            implode(' ', [$attrs['label'] ?? '', $attrs['branch_name'] ?? '', $attrs['url'] ?? ''])
        );

        if ($existing) {
            $updates = [];
            if ($taskId && empty($existing->task_id) && Schema::hasColumn('project_development_links', 'task_id')) {
                $updates['task_id'] = $taskId;
            }
            if (!empty($attrs['status']) && $existing->status !== $attrs['status']) {
                $updates['status'] = $attrs['status'];
            }
            if (!empty($updates)) {
                $existing->update($updates);
            }
            return false;
        }

        $payload = [
            'project_id' => $project->id,
            'user_id'    => Auth::id() ?: $project->user_id,
            'type'       => $type,
            'label'      => $attrs['label'],
            'url'        => $attrs['url'],
            'status'     => $attrs['status'] ?? 'active',
            'linked_at'  => $attrs['linked_at'] ?? now(),
        ];

        if (Schema::hasColumn('project_development_links', 'task_id')) {
            $payload['task_id'] = $taskId;
        }
        if (Schema::hasColumn('project_development_links', 'branch_name')) {
            $payload['branch_name'] = $attrs['branch_name'] ?? null;
        }
        if (Schema::hasColumn('project_development_links', 'commit_sha')) {
            $payload['commit_sha'] = $attrs['commit_sha'] ?? null;
        }
        if (Schema::hasColumn('project_development_links', 'author_name')) {
            $payload['author_name'] = $attrs['author_name'] ?? null;
        }
        if (Schema::hasColumn('project_development_links', 'repo_name')) {
            $payload['repo_name'] = $attrs['repo_name'] ?? null;
        }

        ProjectDevelopmentLink::create($payload);

        return true;
    }

    protected function detectTaskIdFromText(Project $project, string $text): ?int
    {
        if (trim($text) === '') {
            return null;
        }

        $tasks = Task::where('project_id', $project->id)->get(['id', 'issue_key', 'name']);
        if ($tasks->isEmpty()) {
            return null;
        }

        $upperText = strtoupper($text);
        $projectCode = strtoupper(trim((string) ($project->project_code ?: 'PROJ')));

        foreach ($tasks as $t) {
            if (!empty($t->issue_key)) {
                $key = strtoupper(trim($t->issue_key));
                if (preg_match('/\b' . preg_quote($key, '/') . '\b/i', $upperText)) {
                    return (int) $t->id;
                }
            }
        }

        if (preg_match_all('/\b(?:' . preg_quote($projectCode, '/') . '|TP|TASK)-(\d+)\b/i', $upperText, $matches)) {
            foreach ($matches[1] as $num) {
                $candidateId = (int) $num;
                $found = $tasks->firstWhere('id', $candidateId);
                if ($found) {
                    return (int) $found->id;
                }
            }
        }

        return null;
    }

    protected function resolveRepoUrl(Project $project): string
    {
        if (!empty($project->git_repo_url)) {
            return rtrim(preg_replace('/\.git$/i', '', trim($project->git_repo_url)), '/');
        }

        $basePath = base_path();
        if (is_dir($basePath . '/.git')) {
            $remote = @shell_exec('git -C ' . escapeshellarg($basePath) . ' config --get remote.origin.url 2>/dev/null');
            if (is_string($remote) && trim($remote) !== '') {
                $remote = trim($remote);
                if (preg_match('#^git@([^:]+):(.+?)(?:\.git)?$#i', $remote, $m)) {
                    return 'https://' . $m[1] . '/' . $m[2];
                }
                if (preg_match('#^https?://#i', $remote)) {
                    $clean = preg_replace('#^https?://[^@]+@#i', 'https://', $remote);
                    return rtrim(preg_replace('/\.git$/i', '', $clean), '/');
                }
            }
        }

        return config('services.github.default_repo', 'https://github.com/tuantn1807/TaskPilot');
    }

    protected function extractRepoShortName(string $repoUrl): string
    {
        if (preg_match('#(?:github\.com|gitlab\.com|bitbucket\.org)/([^/]+/[^/]+)#i', $repoUrl, $m)) {
            return preg_replace('/\.git$/i', '', $m[1]);
        }

        $parsed = parse_url($repoUrl, PHP_URL_PATH);
        if ($parsed) {
            return trim(preg_replace('/\.git$/i', '', $parsed), '/');
        }

        return 'tuantn1807/TaskPilot';
    }
}
