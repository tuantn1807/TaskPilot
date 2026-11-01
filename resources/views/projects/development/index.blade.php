@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/project-dashboard-luxury.css') }}?v={{ file_exists(public_path('css/project-dashboard-luxury.css')) ? filemtime(public_path('css/project-dashboard-luxury.css')) : time() }}" rel="stylesheet">
<style>
.tp-dev-page {
    width: 100%;
    padding: 0 4px 32px;
    box-sizing: border-box;
}
.tp-dev-repo-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #f8fafc;
    padding: 18px 22px;
    border-radius: 14px;
    margin-bottom: 18px;
    border: 1px solid #334155;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
}
.tp-dev-repo-info {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.tp-dev-repo-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #f8fafc;
}
.tp-dev-repo-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(59, 130, 246, 0.2);
    border: 1px solid rgba(96, 165, 250, 0.35);
    color: #93c5fd;
    font-size: 12px;
    font-weight: 700;
}
.tp-dev-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.tp-dev-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
}
.tp-dev-kpi-val {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}
.tp-dev-kpi-lbl {
    font-size: 12.5px;
    font-weight: 700;
    color: #64748b;
    margin-top: 4px;
}
.tp-dev-kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.tp-dev-layout {
    display: grid;
    grid-template-columns: minmax(0, 2.2fr) minmax(300px, 1fr);
    gap: 18px;
    align-items: start;
}
@media (max-width: 1100px) {
    .tp-dev-layout {
        grid-template-columns: 1fr;
    }
}
.tp-dev-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 18px;
}
.tp-dev-card-header {
    padding: 14px 18px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    background: #f8fafc;
}
.tp-dev-card-title {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.tp-dev-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
}
.tp-dev-filters {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.tp-dev-filter-btn {
    padding: 6px 12px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.tp-dev-filter-btn:hover {
    border-color: #cbd5e1;
    color: #0f172a;
}
.tp-dev-filter-btn.is-active {
    background: #eff6ff;
    border-color: #2563eb;
    color: #2563eb;
}
.tp-dev-table {
    width: 100%;
    border-collapse: collapse;
}
.tp-dev-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}
.tp-dev-table tbody td {
    padding: 13px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
    color: #334155;
    vertical-align: middle;
}
.tp-dev-table tbody tr:last-child td {
    border-bottom: none;
}
.tp-dev-table tbody tr:hover {
    background: #f8fafc;
}
.tp-git-type-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}
.tp-git-type-icon.type-branch { background: #eff6ff; color: #2563eb; }
.tp-git-type-icon.type-commit { background: #f3e8ff; color: #7c3aed; }
.tp-git-type-icon.type-pr { background: #ecfdf5; color: #059669; }
.tp-git-type-icon.type-pipeline { background: #fffbeb; color: #d97706; }
.tp-git-type-icon.type-deployment { background: #fef2f2; color: #dc2626; }
.tp-git-type-icon.type-repo { background: #f1f5f9; color: #0f172a; }

.tp-git-sha {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 5px;
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
}
.tp-git-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}
.tp-git-status.st-open, .tp-git-status.st-active { background: #dbeafe; color: #1d4ed8; }
.tp-git-status.st-merged { background: #ede9fe; color: #6d28d9; }
.tp-git-status.st-success, .tp-git-status.st-committed, .tp-git-status.st-default { background: #d1fae5; color: #047857; }
.tp-git-status.st-failed, .tp-git-status.st-closed { background: #fee2e2; color: #b91c1c; }
.tp-git-status.st-running { background: #fef3c7; color: #b45309; }

.tp-task-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}
.tp-task-item:last-child {
    border-bottom: none;
}
.tp-task-key {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 800;
    color: #2563eb;
    background: #eff6ff;
    padding: 2px 7px;
    border-radius: 5px;
    text-decoration: none;
}
.tp-code-box {
    background: #0f172a;
    color: #e2e8f0;
    padding: 10px 12px;
    border-radius: 8px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    border: 1px solid #334155;
    overflow-x: auto;
}
.tp-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(3px);
    z-index: 1050;
    display: none;
}
.tp-modal-backdrop.open { display: block; }
.tp-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 92%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
    padding: 24px 26px;
    z-index: 1055;
    display: none;
}
.tp-modal.open { display: block; }
.tp-modal h2 {
    margin: 0 0 16px 0;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
}
.tp-field {
    margin-bottom: 13px;
}
.tp-field label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 5px;
}
.tp-field input,
.tp-field select,
.tp-field textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13.5px;
    color: #0f172a;
    background: #ffffff;
    box-sizing: border-box;
}
.tp-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
}

/* Dark mode */
html.tp-theme-dark .tp-dev-kpi-card,
html.tp-theme-dark .tp-dev-card,
html.tp-theme-dark .tp-modal,
html.tp-theme-dark .tp-dev-filter-btn {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
html.tp-theme-dark .tp-dev-card-header,
html.tp-theme-dark .tp-dev-table thead th {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
html.tp-theme-dark .tp-dev-kpi-val,
html.tp-theme-dark .tp-dev-card-title,
html.tp-theme-dark .tp-modal h2,
html.tp-theme-dark .tp-field label {
    color: #f8fafc !important;
}
html.tp-theme-dark .tp-dev-table tbody td,
html.tp-theme-dark .tp-task-item,
html.tp-theme-dark .tp-dev-toolbar {
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}
html.tp-theme-dark .tp-dev-table tbody tr:hover {
    background: rgba(51, 65, 85, 0.35) !important;
}
html.tp-theme-dark .tp-field input,
html.tp-theme-dark .tp-field select,
html.tp-theme-dark .tp-field textarea {
    background: #0f172a !important;
    border-color: #475569 !important;
    color: #f8fafc !important;
}
html.tp-theme-dark .tp-git-sha {
    background: #0f172a !important;
    border-color: #475569 !important;
    color: #93c5fd !important;
}
</style>
@endpush

@section('content')
@php
    $typeMeta = [
        'branch'     => ['label' => 'Branch', 'icon' => 'fa-code-fork'],
        'commit'     => ['label' => 'Commit', 'icon' => 'fa-dot-circle-o'],
        'pr'         => ['label' => 'Pull Request', 'icon' => 'fa-exchange'],
        'pipeline'   => ['label' => 'GitHub Actions', 'icon' => 'fa-cogs'],
        'deployment' => ['label' => 'Deployment', 'icon' => 'fa-rocket'],
        'repo'       => ['label' => 'Repository', 'icon' => 'fa-github'],
    ];
    $provider = $project->git_provider ?: 'github';
    $defaultBranch = $project->git_default_branch ?: 'develop';
    $projectCode = $project->project_code ?: 'TP';
@endphp

<div class="col-xs-12 col-md-12 p-0">
    <div class="tp-dev-page">
        <!-- Toast / Flash Banner -->
        @if(session('success'))
            <div style="margin-bottom: 14px; padding: 12px 16px; border-radius: 10px; background: #ecfdf5; border: 1px solid #10b981; color: #065f46; font-weight: 700; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fa fa-check-circle" style="margin-right: 6px;"></i>{{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#065f46;cursor:pointer;font-size:16px;">&times;</button>
            </div>
        @endif

        <!-- Project Header -->
        <div class="pd-header mb-3" style="margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div class="pd-header-left">
                <div class="pd-breadcrumb" style="margin-bottom: 6px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                    <a href="{{ route('projects.index') }}" style="color: #64748b; text-decoration: none;">Dự án</a>
                    <span>/</span>
                    <a href="{{ route('projects.show', $project) }}" style="color: #64748b; text-decoration: none;">{{ $project->name }}</a>
                    <span>/</span>
                    <span style="color: #0f172a; font-weight: 700;">Development (Tích hợp GitHub)</span>
                </div>
                <div class="pd-title-row" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h1 class="pd-title" style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">{{ $project->name }}</h1>
                    <span class="pd-project-key">{{ $projectCode }}</span>
                    <span class="pd-project-status">{{ strtoupper($project->status ?? 'active') }}</span>
                </div>
                <p style="margin: 6px 0 0 0; color: #64748b; font-size: 13px;">
                    Tích hợp trực tiếp với GitHub: Đồng bộ Branches, Commits, Pull Requests, GitHub Actions CI/CD, tạo nhánh &amp; PR và tự động chuyển trạng thái Task.
                </p>
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <form method="POST" action="{{ route('projects.development.sync', $project) }}" style="display: inline;">
                    @csrf
                    <button type="submit" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; font-weight: 700; cursor: pointer;">
                        <i class="fa fa-github"></i>
                        <span>Đồng bộ từ GitHub</span>
                    </button>
                </form>

                <button type="button" onclick="openModal('gitBranchGenModal')" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border-radius: 8px; border: 1px solid #3b82f6; background: #eff6ff; color: #1d4ed8; font-weight: 700; cursor: pointer;">
                    <i class="fa fa-code-fork"></i>
                    <span>Tạo Branch GitHub</span>
                </button>

                <button type="button" onclick="openModal('githubPrModal')" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border-radius: 8px; border: 1px solid #10b981; background: #ecfdf5; color: #047857; font-weight: 700; cursor: pointer;">
                    <i class="fa fa-exchange"></i>
                    <span>Tạo Pull Request</span>
                </button>

                <button type="button" onclick="openModal('addGitLinkModal')" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 15px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 700; cursor: pointer;">
                    <i class="fa fa-plus-circle"></i>
                    <span>Thêm liên kết</span>
                </button>
            </div>
        </div>

        <!-- Unified Project Sub-Nav Bar -->
        @include('projects.partials.nav', ['project' => $project, 'active' => 'development'])

        <!-- Connected GitHub Repository Banner -->
        <div class="tp-dev-repo-banner">
            <div class="tp-dev-repo-info">
                <div class="tp-dev-repo-icon">
                    <i class="fa fa-github"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <a href="{{ $detectedRepoUrl }}" target="_blank" rel="noopener" style="color: #ffffff; font-size: 16px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <span>{{ $repoShortName }}</span>
                            <i class="fa fa-external-link" style="font-size: 12px; opacity: 0.75;"></i>
                        </a>
                        <span class="tp-dev-repo-chip">
                            <i class="fa fa-code-fork"></i>
                            <span>Default: {{ $defaultBranch }}</span>
                        </span>
                        @if($hasGithubToken)
                            <span class="tp-dev-repo-chip" style="background: rgba(16, 185, 129, 0.22); border-color: rgba(52, 211, 153, 0.45); color: #6ee7b7;">
                                <i class="fa fa-key"></i>
                                <span>GitHub Token: Đã kết nối</span>
                            </span>
                        @else
                            <span class="tp-dev-repo-chip" style="background: rgba(245, 158, 11, 0.2); border-color: rgba(251, 191, 36, 0.4); color: #fde68a;">
                                <i class="fa fa-globe"></i>
                                <span>Chế độ Public API &amp; Local Git</span>
                            </span>
                        @endif
                        <span id="githubLiveMetaBadge" style="display: none;" class="tp-dev-repo-chip"></span>
                    </div>
                    <div style="font-size: 12.5px; color: #94a3b8; margin-top: 5px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <span>Repo: <code style="color: #cbd5e1;">{{ $detectedRepoUrl }}</code></span>
                        <span>Webhook: <code style="color: #93c5fd;">{{ $webhookUrl }}</code></span>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <button type="button" onclick="testGithubConnection(this)" style="padding: 7px 12px; border-radius: 8px; background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(52, 211, 153, 0.4); color: #6ee7b7; font-size: 12.5px; font-weight: 700; cursor: pointer;">
                    <i class="fa fa-plug"></i> Kiểm tra kết nối GitHub
                </button>
                <a href="{{ $detectedRepoUrl }}/pulls" target="_blank" rel="noopener" style="padding: 7px 12px; border-radius: 8px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #f8fafc; font-size: 12.5px; font-weight: 700; text-decoration: none;">
                    <i class="fa fa-exchange"></i> GitHub PRs
                </a>
                <a href="{{ $detectedRepoUrl }}/actions" target="_blank" rel="noopener" style="padding: 7px 12px; border-radius: 8px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #f8fafc; font-size: 12.5px; font-weight: 700; text-decoration: none;">
                    <i class="fa fa-cogs"></i> Actions
                </a>
                <button type="button" onclick="openModal('repoConfigModal')" style="padding: 7px 13px; border-radius: 8px; background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.28); color: #ffffff; font-size: 12.5px; font-weight: 700; cursor: pointer;">
                    <i class="fa fa-cog"></i> Cấu hình GitHub
                </button>
            </div>
        </div>

        <!-- 5 KPI Summary Cards -->
        <div class="tp-dev-kpi-grid">
            <div class="tp-dev-kpi-card">
                <div>
                    <div class="tp-dev-kpi-val">{{ $stats['branches'] }}</div>
                    <div class="tp-dev-kpi-lbl">Nhánh (Branches)</div>
                </div>
                <div class="tp-dev-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                    <i class="fa fa-code-fork"></i>
                </div>
            </div>

            <div class="tp-dev-kpi-card">
                <div>
                    <div class="tp-dev-kpi-val">{{ $stats['commits'] }}</div>
                    <div class="tp-dev-kpi-lbl">Commits đã đồng bộ</div>
                </div>
                <div class="tp-dev-kpi-icon" style="background: #f3e8ff; color: #7c3aed;">
                    <i class="fa fa-dot-circle-o"></i>
                </div>
            </div>

            <div class="tp-dev-kpi-card">
                <div>
                    <div class="tp-dev-kpi-val">
                        {{ $stats['prs'] }}
                        <span style="font-size: 12px; font-weight: 700; color: #059669; margin-left: 4px;">
                            ({{ $stats['prs_open'] }} open / {{ $stats['prs_merged'] }} merged)
                        </span>
                    </div>
                    <div class="tp-dev-kpi-lbl">GitHub Pull Requests</div>
                </div>
                <div class="tp-dev-kpi-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="fa fa-exchange"></i>
                </div>
            </div>

            <div class="tp-dev-kpi-card">
                <div>
                    <div class="tp-dev-kpi-val">{{ $stats['pipelines'] }}</div>
                    <div class="tp-dev-kpi-lbl">GitHub Actions &amp; Deploy</div>
                </div>
                <div class="tp-dev-kpi-icon" style="background: #fffbeb; color: #d97706;">
                    <i class="fa fa-cogs"></i>
                </div>
            </div>

            <div class="tp-dev-kpi-card">
                <div>
                    <div class="tp-dev-kpi-val">{{ $stats['linked_tasks'] }} <span style="font-size: 13px; color: #64748b; font-weight: 600;">/ {{ $allTasks->count() }}</span></div>
                    <div class="tp-dev-kpi-lbl">Task đã gắn nhánh / code</div>
                </div>
                <div class="tp-dev-kpi-icon" style="background: #eef2ff; color: #4f46e5;">
                    <i class="fa fa-link"></i>
                </div>
            </div>
        </div>

        <!-- Main 2-Column Layout -->
        <div class="tp-dev-layout">
            <!-- Left Column: Git Activity & Links Table -->
            <div>
                <div class="tp-dev-card">
                    <div class="tp-dev-card-header">
                        <h2 class="tp-dev-card-title">
                            <i class="fa fa-github" style="color: #0f172a;"></i>
                            <span>Hoạt động GitHub &amp; Liên kết Mã nguồn ({{ $links->count() }})</span>
                        </h2>
                        <span style="font-size: 12px; color: #64748b;">
                            Smart Commits: tự động gắn Task khi có mã <code>{{ $projectCode }}-ID</code> trong Branch / Commit / PR
                        </span>
                    </div>

                    <!-- Filter & Search Toolbar -->
                    <div class="tp-dev-toolbar">
                        <div class="tp-dev-filters">
                            @foreach([
                                'all'        => 'Tất cả (' . $links->count() . ')',
                                'branch'     => 'Branches (' . $stats['branches'] . ')',
                                'commit'     => 'Commits (' . $stats['commits'] . ')',
                                'pr'         => 'Pull Requests (' . $stats['prs'] . ')',
                                'pipeline'   => 'GitHub Actions (' . $stats['pipelines'] . ')',
                                'deployment' => 'Deployments',
                            ] as $fKey => $fLabel)
                                <button type="button" onclick="filterGitLinks('{{ $fKey }}')" data-filter="{{ $fKey }}" class="tp-dev-filter-btn {{ $fKey === 'all' ? 'is-active' : '' }}">
                                    {{ $fLabel }}
                                </button>
                            @endforeach
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                            <select id="gitTaskFilter" onchange="applyGitFilters()" style="padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; color: #0f172a; background: #ffffff;">
                                <option value="">-- Lọc theo Task --</option>
                                @foreach($allTasks as $t)
                                    @php $tKey = $t->issue_key ?: ($projectCode . '-' . $t->id); @endphp
                                    <option value="{{ $t->id }}">[{{ $tKey }}] {{ \Illuminate\Support\Str::limit($t->name, 40) }}</option>
                                @endforeach
                            </select>

                            <div style="position: relative; min-width: 210px;">
                                <i class="fa fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px;"></i>
                                <input type="text" id="gitSearchInput" oninput="applyGitFilters()" placeholder="Tìm nhánh, SHA, commit, PR..." style="width: 100%; padding: 7px 10px 7px 30px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                            </div>
                        </div>
                    </div>

                    @if($links->isEmpty())
                        <div style="text-align: center; padding: 48px 20px; color: #64748b;">
                            <i class="fa fa-github" style="font-size: 44px; color: #94a3b8; margin-bottom: 12px; display: block;"></i>
                            <h3 style="margin: 0 0 6px 0; font-size: 17px; font-weight: 800; color: #0f172a;">Chưa có dữ liệu đồng bộ từ GitHub</h3>
                            <p style="margin: 0 0 18px 0; font-size: 13.5px;">
                                Bấm <strong>"Đồng bộ từ GitHub"</strong> để lấy toàn bộ Branches, Commits, Pull Requests &amp; GitHub Actions về dự án.
                            </p>
                            <div style="display: inline-flex; gap: 10px; flex-wrap: wrap;">
                                <form method="POST" action="{{ route('projects.development.sync', $project) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="padding: 9px 18px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 700; cursor: pointer;">
                                        <i class="fa fa-github"></i> Đồng bộ từ GitHub ngay
                                    </button>
                                </form>
                                <button type="button" onclick="openModal('gitBranchGenModal')" style="padding: 9px 18px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; font-weight: 700; cursor: pointer;">
                                    <i class="fa fa-code-fork"></i> Tạo nhánh theo Task
                                </button>
                            </div>
                        </div>
                    @else
                        <div style="overflow-x: auto;">
                            <table class="tp-dev-table" id="gitLinksTable">
                                <thead>
                                    <tr>
                                        <th style="width: 115px;">Loại</th>
                                        <th>Chi tiết Nhánh / Commit / PR</th>
                                        <th>Công việc liên kết</th>
                                        <th>Tác giả / Thời gian</th>
                                        <th>Trạng thái</th>
                                        <th style="width: 80px; text-align: right;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($links as $link)
                                        @php
                                            $meta = $typeMeta[$link->type] ?? ['label' => ucfirst($link->type), 'icon' => 'fa-code'];
                                            $linkedTask = $link->task;
                                            $linkedTaskKey = $linkedTask ? ($linkedTask->issue_key ?: ($projectCode . '-' . $linkedTask->id)) : '';
                                            $copyText = $link->type === 'branch'
                                                ? ($link->branch_name ?: $link->label)
                                                : ($link->commit_sha ?: $link->label);
                                            $searchHaystack = mb_strtolower(implode(' ', [
                                                $link->label,
                                                $link->branch_name,
                                                $link->commit_sha,
                                                $link->author_name,
                                                $linkedTaskKey,
                                                $linkedTask->name ?? '',
                                            ]));
                                        @endphp
                                        <tr data-type="{{ $link->type }}" data-task-id="{{ $link->task_id ?? '' }}" data-search="{{ $searchHaystack }}">
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <span class="tp-git-type-icon type-{{ $link->type }}">
                                                        <i class="fa {{ $meta['icon'] }}"></i>
                                                    </span>
                                                    <span style="font-size: 12px; font-weight: 700; color: #475569;">{{ $meta['label'] }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                    @if(!empty($link->commit_sha))
                                                        <span class="tp-git-sha" title="SHA / PR ID">{{ $link->commit_sha }}</span>
                                                    @endif
                                                    <a href="{{ $link->url }}" target="_blank" rel="noopener" style="font-weight: 700; color: #2563eb; text-decoration: none; word-break: break-word;">
                                                        {{ $link->label }}
                                                        <i class="fa fa-external-link" style="font-size: 11px; margin-left: 3px; opacity: 0.7;"></i>
                                                    </a>
                                                    <button type="button" onclick="copyTextToClipboard('{{ addslashes($copyText) }}', this)" title="Sao chép '{{ $copyText }}'" style="padding: 2px 7px; border-radius: 5px; border: 1px solid #cbd5e1; background: transparent; color: #64748b; font-size: 11px; cursor: pointer;">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                    @if($link->type === 'branch' && !in_array($link->label, ['main', 'master', 'develop'], true))
                                                        <button
                                                            type="button"
                                                            onclick="openPrModalForBranch('{{ addslashes($link->branch_name ?: $link->label) }}', '{{ $link->task_id ?? '' }}')"
                                                            style="padding: 2px 8px; border-radius: 5px; border: 1px solid #a7f3d0; background: #ecfdf5; color: #047857; font-size: 11px; font-weight: 700; cursor: pointer;"
                                                            title="Tạo Pull Request từ nhánh này"
                                                        >
                                                            <i class="fa fa-exchange"></i> Tạo PR
                                                        </button>
                                                    @endif
                                                </div>
                                                @if(!empty($link->branch_name) && $link->branch_name !== $link->label)
                                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 3px;">
                                                        <i class="fa fa-code-fork"></i> Nhánh: <code>{{ $link->branch_name }}</code>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($linkedTask)
                                                    <a href="{{ route('tasks.show', $linkedTask->id) }}" class="tp-task-key" title="{{ $linkedTask->name }}">
                                                        {{ $linkedTaskKey }}
                                                    </a>
                                                    <div style="font-size: 12px; color: #475569; margin-top: 3px; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        {{ $linkedTask->name }}
                                                    </div>
                                                @else
                                                    <span style="color: #94a3b8; font-size: 12px;">— Chưa gắn Task</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; font-size: 12.5px;">
                                                    {{ $link->author_name ?: ($link->user->full_name ?? $link->user->name ?? 'Developer') }}
                                                </div>
                                                <div style="font-size: 11.5px; color: #64748b;">
                                                    {{ $link->linked_at ? $link->linked_at->format('d/m/Y H:i') : ($link->created_at ? $link->created_at->format('d/m/Y H:i') : '—') }}
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST" action="{{ route('projects.development.status', [$project, $link]) }}" style="display: inline;">
                                                    @csrf
                                                    <select name="status" onchange="this.form.submit()" class="tp-git-status st-{{ $link->status ?: 'active' }}" style="border: none; cursor: pointer; outline: none;">
                                                        @foreach(['active' => 'ACTIVE', 'default' => 'DEFAULT', 'open' => 'OPEN', 'merged' => 'MERGED', 'closed' => 'CLOSED', 'committed' => 'COMMITTED', 'running' => 'RUNNING', 'success' => 'SUCCESS', 'failed' => 'FAILED'] as $stKey => $stLabel)
                                                            <option value="{{ $stKey }}" {{ ($link->status ?: 'active') === $stKey ? 'selected' : '' }} style="background: #fff; color: #0f172a;">
                                                                {{ $stLabel }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            </td>
                                            <td style="text-align: right;">
                                                <form method="POST" action="{{ route('projects.development.destroy', [$project, $link]) }}" onsubmit="return confirm('Xóa liên kết Git này?')" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Xóa liên kết" style="padding: 5px 9px; border-radius: 6px; border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; cursor: pointer;">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Active Tasks & Smart GitHub Helper -->
            <div>
                <!-- Active Development Tasks Card -->
                <div class="tp-dev-card">
                    <div class="tp-dev-card-header">
                        <h2 class="tp-dev-card-title">
                            <i class="fa fa-bolt" style="color: #f59e0b;"></i>
                            <span>Công việc đang phát triển ({{ $activeTasks->count() }})</span>
                        </h2>
                    </div>

                    @if($activeTasks->isEmpty())
                        <div style="padding: 24px 16px; text-align: center; color: #64748b; font-size: 13px;">
                            Hiện không có công việc nào đang mở.
                        </div>
                    @else
                        <div style="max-height: 480px; overflow-y: auto;">
                            @foreach($activeTasks->take(15) as $task)
                                @php
                                    $tKey = $task->issue_key ?: ($projectCode . '-' . $task->id);
                                    $tLinksCount = $task->developmentLinks ? $task->developmentLinks->count() : 0;
                                @endphp
                                <div class="tp-task-item">
                                    <div style="min-width: 0; flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 3px;">
                                            <a href="{{ route('tasks.show', $task->id) }}" class="tp-task-key">{{ $tKey }}</a>
                                            <span style="font-size: 11px; font-weight: 700; padding: 1px 6px; border-radius: 4px; background: #f1f5f9; color: #475569; text-transform: uppercase;">
                                                {{ $task->status }}
                                            </span>
                                            @if($tLinksCount > 0)
                                                <span style="font-size: 11px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 1px 6px; border-radius: 4px;">
                                                    <i class="fa fa-code-fork"></i> {{ $tLinksCount }} Git
                                                </span>
                                            @endif
                                        </div>
                                        <a href="{{ route('tasks.show', $task->id) }}" style="font-size: 13px; font-weight: 700; color: #0f172a; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $task->name }}">
                                            {{ $task->name }}
                                        </a>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                            <i class="fa fa-user-o"></i> {{ $task->assignee->full_name ?? $task->assignee->name ?? 'Chưa phân công' }}
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        onclick="openBranchGeneratorForTask({{ $task->id }}, '{{ addslashes($tKey) }}', '{{ addslashes($task->name) }}', '{{ addslashes($task->issue_type ?? 'task') }}')"
                                        style="padding: 6px 10px; border-radius: 7px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 11.5px; font-weight: 700; cursor: pointer; white-space: nowrap;"
                                        title="Tạo nhánh GitHub cho {{ $tKey }}"
                                    >
                                        <i class="fa fa-code-fork"></i> Tạo Branch
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- GitHub Automation & Smart Commits Guide Card -->
                <div class="tp-dev-card">
                    <div class="tp-dev-card-header">
                        <h2 class="tp-dev-card-title">
                            <i class="fa fa-magic" style="color: #10b981;"></i>
                            <span>Tự động hóa GitHub &amp; Smart Commits</span>
                        </h2>
                    </div>
                    <div style="padding: 15px 18px; font-size: 12.5px; color: #475569; line-height: 1.55;">
                        <p style="margin: 0 0 10px 0;">
                            Khi tên nhánh, commit hoặc Pull Request trên GitHub chứa mã Task (ví dụ <code>{{ $projectCode }}-42</code>), TaskPilot tự động:
                        </p>
                        <ul style="margin: 0 0 12px 16px; padding: 0;">
                            <li>Tạo Branch / Push Commit &rarr; chuyển Task sang <strong>In Progress</strong></li>
                            <li>Mở Pull Request &rarr; chuyển Task sang <strong>Review / In Progress</strong></li>
                            <li>Merge Pull Request &rarr; tự động hoàn thành Task (<strong>Done</strong>)</li>
                        </ul>
                        <div style="margin-bottom: 6px; font-weight: 700; color: #0f172a;">GitHub Webhook URL:</div>
                        <div class="tp-code-box">
                            <code style="font-size: 11.5px;">{{ $webhookUrl }}</code>
                            <button type="button" onclick="copyTextToClipboard('{{ addslashes($webhookUrl) }}', this)" style="padding: 3px 8px; border-radius: 5px; border: 1px solid #475569; background: #1e293b; color: #fff; font-size: 11px; cursor: pointer;">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 1. Modal: Add Git Link -->
<div class="tp-modal-backdrop" id="addGitLinkModalBackdrop" onclick="closeModal('addGitLinkModal')"></div>
<div class="tp-modal" id="addGitLinkModal" role="dialog" aria-modal="true">
    <h2><i class="fa fa-code-fork" style="color: #2563eb; margin-right: 8px;"></i>Thêm liên kết Phát triển (GitHub / Git)</h2>
    <form method="POST" action="{{ route('projects.development.store', $project) }}">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="tp-field">
                <label>Loại liên kết Git <span style="color: #dc2626;">*</span></label>
                <select name="type" id="addGitTypeSelect">
                    <option value="branch">Nhánh (Git Branch)</option>
                    <option value="pr">Pull Request (GitHub PR)</option>
                    <option value="commit">Commit (Git Commit)</option>
                    <option value="pipeline">GitHub Actions (CI/CD Pipeline)</option>
                    <option value="deployment">Deployment (Staging / Production)</option>
                </select>
            </div>

            <div class="tp-field">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="active">Active (Đang hoạt động)</option>
                    <option value="open">Open (Đang mở PR)</option>
                    <option value="merged">Merged (Đã gộp)</option>
                    <option value="committed">Committed</option>
                    <option value="success">Success (CI/CD Pass)</option>
                    <option value="running">Running (Đang chạy)</option>
                    <option value="failed">Failed (Lỗi)</option>
                    <option value="closed">Closed (Đã đóng)</option>
                </select>
            </div>
        </div>

        <div class="tp-field">
            <label>Gắn với Công việc (Task) trong dự án</label>
            <select name="task_id">
                <option value="">-- Tự động nhận diện từ mã Task hoặc chọn thủ công --</option>
                @foreach($allTasks as $t)
                    @php $tKey = $t->issue_key ?: ($projectCode . '-' . $t->id); @endphp
                    <option value="{{ $t->id }}">[{{ $tKey }}] {{ $t->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="tp-field">
            <label>Tên nhánh / Tiêu đề Commit / PR <span style="color: #dc2626;">*</span></label>
            <input name="label" required maxlength="255" placeholder="Ví dụ: feature/{{ $projectCode }}-42-github-integration">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="tp-field">
                <label>Tên nhánh (Branch)</label>
                <input name="branch_name" placeholder="feature/{{ $projectCode }}-42-...">
            </div>
            <div class="tp-field">
                <label>Mã Commit SHA / Số PR</label>
                <input name="commit_sha" placeholder="Ví dụ: 87adb93 hoặc #101">
            </div>
        </div>

        <div class="tp-field">
            <label>Đường dẫn URL trên GitHub (Để trống sẽ tự tạo theo Repo)</label>
            <input name="url" placeholder="{{ $detectedRepoUrl }}/tree/feature/...">
        </div>

        <div class="tp-modal-footer">
            <button type="button" onclick="closeModal('addGitLinkModal')" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: transparent; color: #475569; font-weight: 700; cursor: pointer;">
                Hủy
            </button>
            <button type="submit" style="padding: 8px 18px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 700; cursor: pointer;">
                <i class="fa fa-check"></i> Lưu liên kết
            </button>
        </div>
    </form>
</div>

<!-- 2. Modal: Create GitHub Branch & Command Generator -->
<div class="tp-modal-backdrop" id="gitBranchGenModalBackdrop" onclick="closeModal('gitBranchGenModal')"></div>
<div class="tp-modal" id="gitBranchGenModal" role="dialog" aria-modal="true">
    <h2><i class="fa fa-github" style="color: #0f172a; margin-right: 8px;"></i>Tạo nhánh trên GitHub &amp; Sinh lệnh Git theo Task</h2>

    <form method="POST" action="{{ route('projects.development.github.branch', $project) }}">
        @csrf
        <div class="tp-field">
            <label>Chọn công việc cần phát triển</label>
            <select name="task_id" id="genTaskSelect" onchange="onSelectGeneratorTask(this)">
                <option value="" data-key="{{ $projectCode }}-1" data-title="trien-khai-tinh-nang" data-type="feature">-- Chọn công việc --</option>
                @foreach($allTasks as $t)
                    @php
                        $tKey = $t->issue_key ?: ($projectCode . '-' . $t->id);
                        $prefix = ($t->issue_type === 'bug') ? 'fix' : 'feature';
                    @endphp
                    <option value="{{ $t->id }}" data-key="{{ $tKey }}" data-title="{{ $t->name }}" data-type="{{ $prefix }}">
                        [{{ $tKey }}] {{ $t->name }} ({{ $t->status }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 120px 1fr 140px; gap: 10px;">
            <div class="tp-field">
                <label>Tiền tố</label>
                <select id="genBranchPrefix" onchange="rebuildGitCommands()">
                    <option value="feature">feature/</option>
                    <option value="fix">fix/</option>
                    <option value="hotfix">hotfix/</option>
                    <option value="chore">chore/</option>
                    <option value="refactor">refactor/</option>
                </select>
            </div>
            <div class="tp-field">
                <label>Tên nhánh mới (Branch Name) <span style="color:#dc2626;">*</span></label>
                <input type="text" name="branch_name" id="genBranchNameInput" required oninput="rebuildGitCommands()" value="feature/{{ $projectCode }}-1-trien-khai-tinh-nang">
            </div>
            <div class="tp-field">
                <label>Tạo từ nhánh gốc</label>
                <input type="text" name="base_branch" value="{{ $defaultBranch }}" placeholder="develop">
            </div>
        </div>

        <div class="tp-field">
            <label>1. Lệnh Checkout ở máy local:</label>
            <div class="tp-code-box">
                <code id="cmdCheckoutText">git checkout -b feature/{{ $projectCode }}-1-trien-khai-tinh-nang</code>
                <button type="button" onclick="copyTextToClipboard(document.getElementById('cmdCheckoutText').textContent, this)" style="padding: 4px 10px; border-radius: 6px; border: 1px solid #475569; background: #1e293b; color: #f8fafc; font-size: 11.5px; cursor: pointer; white-space: nowrap;">
                    <i class="fa fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div class="tp-field">
            <label>2. Lệnh Commit chuẩn Smart Commits:</label>
            <div class="tp-code-box">
                <code id="cmdCommitText">git commit -m "feat: [{{ $projectCode }}-1] Triển khai tính năng"</code>
                <button type="button" onclick="copyTextToClipboard(document.getElementById('cmdCommitText').textContent, this)" style="padding: 4px 10px; border-radius: 6px; border: 1px solid #475569; background: #1e293b; color: #f8fafc; font-size: 11.5px; cursor: pointer; white-space: nowrap;">
                    <i class="fa fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 10px;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #334155; cursor: pointer;">
                <input type="checkbox" name="push_to_github" value="1" checked style="width: auto;">
                <span>Tạo nhánh trực tiếp trên <strong>GitHub Repository ({{ $repoShortName }})</strong> qua API (nếu đã cấu hình Token)</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #334155; cursor: pointer;">
                <input type="checkbox" name="auto_move_task" value="1" checked style="width: auto;">
                <span>Tự động chuyển công việc sang trạng thái <strong>Đang thực hiện (In Progress)</strong></span>
            </label>
        </div>

        <div class="tp-modal-footer">
            <button type="button" onclick="closeModal('gitBranchGenModal')" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: transparent; color: #475569; font-weight: 700; cursor: pointer;">
                Đóng
            </button>
            <button type="submit" style="padding: 8px 18px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 700; cursor: pointer;">
                <i class="fa fa-github"></i> Tạo &amp; Ghi nhận nhánh GitHub
            </button>
        </div>
    </form>
</div>

<!-- 3. Modal: Create GitHub Pull Request -->
<div class="tp-modal-backdrop" id="githubPrModalBackdrop" onclick="closeModal('githubPrModal')"></div>
<div class="tp-modal" id="githubPrModal" role="dialog" aria-modal="true">
    <h2><i class="fa fa-exchange" style="color: #059669; margin-right: 8px;"></i>Tạo Pull Request trên GitHub ({{ $repoShortName }})</h2>
    <form method="POST" action="{{ route('projects.development.github.pr', $project) }}">
        @csrf
        <div class="tp-field">
            <label>Gắn với Công việc (Task)</label>
            <select name="task_id" id="prTaskSelect" onchange="onSelectPrTask(this)">
                <option value="">-- Chọn công việc (Tùy chọn) --</option>
                @foreach($allTasks as $t)
                    @php $tKey = $t->issue_key ?: ($projectCode . '-' . $t->id); @endphp
                    <option value="{{ $t->id }}" data-key="{{ $tKey }}" data-title="{{ $t->name }}">
                        [{{ $tKey }}] {{ $t->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="tp-field">
                <label>Nhánh nguồn (Head Branch) <span style="color:#dc2626;">*</span></label>
                <input type="text" name="head" id="prHeadInput" list="projectBranchesList" required placeholder="feature/{{ $projectCode }}-42-...">
                <datalist id="projectBranchesList">
                    @foreach($branchLinks as $bLink)
                        <option value="{{ $bLink->branch_name ?: $bLink->label }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="tp-field">
                <label>Nhánh đích (Base Branch) <span style="color:#dc2626;">*</span></label>
                <input type="text" name="base" id="prBaseInput" required value="{{ $defaultBranch }}">
            </div>
        </div>

        <div class="tp-field">
            <label>Tiêu đề Pull Request <span style="color:#dc2626;">*</span></label>
            <input type="text" name="title" id="prTitleInput" required maxlength="255" placeholder="feat(module): [{{ $projectCode }}-42] Mô tả tính năng...">
        </div>

        <div class="tp-field">
            <label>Mô tả chi tiết (PR Description)</label>
            <textarea name="body" id="prBodyInput" rows="3" placeholder="Tóm tắt các thay đổi chính trong Pull Request..."></textarea>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;" class="tp-modal-footer">
            <button type="button" onclick="openGithubCompareNewTab()" style="padding: 8px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; color: #0f172a; font-weight: 700; cursor: pointer; font-size: 12.5px;">
                <i class="fa fa-external-link"></i> Mở trang Compare trên GitHub
            </button>
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="closeModal('githubPrModal')" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: transparent; color: #475569; font-weight: 700; cursor: pointer;">
                    Hủy
                </button>
                <button type="submit" style="padding: 8px 18px; border-radius: 8px; border: none; background: #059669; color: #ffffff; font-weight: 700; cursor: pointer;">
                    <i class="fa fa-github"></i> Tạo Pull Request
                </button>
            </div>
        </div>
    </form>
</div>

<!-- 4. Modal: Configure GitHub Repository & Token & Webhook -->
<div class="tp-modal-backdrop" id="repoConfigModalBackdrop" onclick="closeModal('repoConfigModal')"></div>
<div class="tp-modal" id="repoConfigModal" role="dialog" aria-modal="true">
    <h2><i class="fa fa-github" style="color: #0f172a; margin-right: 8px;"></i>Cấu hình Tích hợp GitHub</h2>
    <form method="POST" action="{{ route('projects.development.repo', $project) }}">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="tp-field">
                <label>Nền tảng Git</label>
                <select name="git_provider">
                    <option value="github" {{ $provider === 'github' ? 'selected' : '' }}>GitHub (github.com)</option>
                    <option value="gitlab" {{ $provider === 'gitlab' ? 'selected' : '' }}>GitLab (gitlab.com)</option>
                    <option value="bitbucket" {{ $provider === 'bitbucket' ? 'selected' : '' }}>Bitbucket (bitbucket.org)</option>
                    <option value="local" {{ $provider === 'local' ? 'selected' : '' }}>Local Git</option>
                </select>
            </div>

            <div class="tp-field">
                <label>Nhánh mặc định (Default Branch)</label>
                <input type="text" name="git_default_branch" value="{{ $defaultBranch }}" placeholder="develop hoặc main">
            </div>
        </div>

        <div class="tp-field">
            <label>Đường dẫn GitHub Repository URL <span style="color: #dc2626;">*</span></label>
            <input type="url" name="git_repo_url" required value="{{ $detectedRepoUrl }}" placeholder="https://github.com/owner/repository">
        </div>

        <div class="tp-field">
            <label>
                GitHub Personal Access Token (Tùy chọn - Dùng cho Private Repo &amp; Tạo Branch/PR trực tiếp)
            </label>
            <input
                type="password"
                name="git_token"
                value="{{ !empty($project->git_token) ? '••••••••••••••••••••' : '' }}"
                placeholder="ghp_xxxxxxxxxxxxxxxxxxxx hoặc github_pat_..."
            >
            <div style="font-size: 11.5px; color: #64748b; margin-top: 4px; display: flex; justify-content: space-between; align-items: center;">
                <span>Để trống nếu dùng Repo Public hoặc cấu hình chung <code>GITHUB_TOKEN</code> trong <code>.env</code>.</span>
                @if(!empty($project->git_token))
                    <label style="display: inline-flex; align-items: center; gap: 4px; color: #dc2626; cursor: pointer; font-weight: 700;">
                        <input type="checkbox" name="clear_git_token" value="1" style="width: auto;"> Xóa Token đã lưu
                    </label>
                @endif
            </div>
        </div>

        <div class="tp-field">
            <label>GitHub Webhook Endpoint (Dán vào Settings &rarr; Webhooks trên GitHub)</label>
            <div class="tp-code-box" style="margin-bottom: 8px;">
                <code style="font-size: 12px;">{{ $webhookUrl }}</code>
                <button type="button" onclick="copyTextToClipboard('{{ addslashes($webhookUrl) }}', this)" style="padding: 3px 8px; border-radius: 5px; border: 1px solid #475569; background: #1e293b; color: #fff; font-size: 11px; cursor: pointer;">
                    <i class="fa fa-copy"></i> Copy
                </button>
            </div>
            <input type="text" name="git_webhook_secret" value="{{ $project->git_webhook_secret }}" placeholder="Webhook Secret (Tùy chọn để xác thực chữ ký X-Hub-Signature-256)">
        </div>

        <div class="tp-field" style="margin-top: 8px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="git_auto_transition" value="1" {{ ($project->git_auto_transition ?? true) ? 'checked' : '' }} style="width: auto;">
                <span>Tự động chuyển trạng thái Task khi Tạo nhánh, Push Commit hoặc Merge PR trên GitHub</span>
            </label>
        </div>

        <div class="tp-modal-footer">
            <button type="button" onclick="closeModal('repoConfigModal')" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: transparent; color: #475569; font-weight: 700; cursor: pointer;">
                Hủy
            </button>
            <button type="submit" style="padding: 8px 18px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 700; cursor: pointer;">
                <i class="fa fa-save"></i> Lưu cấu hình GitHub
            </button>
        </div>
    </form>
</div>

<script>
var currentGitTypeFilter = 'all';
var currentGitTaskKey = '{{ $projectCode }}-1';
var currentGitTaskTitle = 'Triển khai tính năng';
var currentRepoUrl = '{{ addslashes($detectedRepoUrl) }}';

function openModal(id) {
    var bd = document.getElementById(id + 'Backdrop');
    var md = document.getElementById(id);
    if (bd) bd.classList.add('open');
    if (md) md.classList.add('open');
}

function closeModal(id) {
    var bd = document.getElementById(id + 'Backdrop');
    var md = document.getElementById(id);
    if (bd) bd.classList.remove('open');
    if (md) md.classList.remove('open');
}

function slugifyGitText(str) {
    if (!str) return 'task';
    return str
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd').replace(/Đ/g, 'D')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .substring(0, 48);
}

function onSelectGeneratorTask(selectEl) {
    var opt = selectEl.options[selectEl.selectedIndex];
    if (!opt) return;
    var key = opt.getAttribute('data-key') || '{{ $projectCode }}-1';
    var title = opt.getAttribute('data-title') || 'trien-khai-tinh-nang';
    var prefix = opt.getAttribute('data-type') || 'feature';

    currentGitTaskKey = key;
    currentGitTaskTitle = title;

    document.getElementById('genBranchPrefix').value = prefix;

    var slug = slugifyGitText(title);
    var branchName = prefix + '/' + key + '-' + slug;
    document.getElementById('genBranchNameInput').value = branchName;
    rebuildGitCommands();
}

function openBranchGeneratorForTask(taskId, taskKey, taskTitle, issueType) {
    var prefix = (issueType === 'bug') ? 'fix' : 'feature';
    currentGitTaskKey = taskKey;
    currentGitTaskTitle = taskTitle;

    var selectEl = document.getElementById('genTaskSelect');
    if (selectEl) {
        selectEl.value = String(taskId);
    }
    document.getElementById('genBranchPrefix').value = prefix;

    var slug = slugifyGitText(taskTitle);
    var branchName = prefix + '/' + taskKey + '-' + slug;
    document.getElementById('genBranchNameInput').value = branchName;
    rebuildGitCommands();
    openModal('gitBranchGenModal');
}

function rebuildGitCommands() {
    var prefix = document.getElementById('genBranchPrefix').value || 'feature';
    var rawInput = document.getElementById('genBranchNameInput').value.trim();

    if (rawInput.indexOf('/') !== -1) {
        var afterSlash = rawInput.split('/').slice(1).join('/');
        rawInput = prefix + '/' + afterSlash;
        document.getElementById('genBranchNameInput').value = rawInput;
    }

    var commitPrefix = (prefix === 'fix' || prefix === 'hotfix') ? 'fix' : 'feat';
    document.getElementById('cmdCheckoutText').textContent = 'git checkout -b ' + rawInput;
    document.getElementById('cmdCommitText').textContent = 'git commit -m "' + commitPrefix + ': [' + currentGitTaskKey + '] ' + currentGitTaskTitle.replace(/"/g, "'") + '"';
}

function onSelectPrTask(selectEl) {
    var opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !selectEl.value) return;
    var key = opt.getAttribute('data-key') || '';
    var title = opt.getAttribute('data-title') || '';
    if (key && title) {
        document.getElementById('prTitleInput').value = 'feat: [' + key + '] ' + title;
        var slug = slugifyGitText(title);
        if (!document.getElementById('prHeadInput').value) {
            document.getElementById('prHeadInput').value = 'feature/' + key + '-' + slug;
        }
    }
}

function openPrModalForBranch(branchName, taskId) {
    document.getElementById('prHeadInput').value = branchName;
    if (taskId) {
        var sel = document.getElementById('prTaskSelect');
        if (sel) {
            sel.value = String(taskId);
            onSelectPrTask(sel);
        }
    }
    if (!document.getElementById('prTitleInput').value) {
        document.getElementById('prTitleInput').value = 'Merge branch ' + branchName;
    }
    openModal('githubPrModal');
}

function openGithubCompareNewTab() {
    var base = encodeURIComponent(document.getElementById('prBaseInput').value.trim() || '{{ $defaultBranch }}');
    var head = encodeURIComponent(document.getElementById('prHeadInput').value.trim() || 'develop');
    var title = encodeURIComponent(document.getElementById('prTitleInput').value.trim());
    var body = encodeURIComponent(document.getElementById('prBodyInput').value.trim());
    var url = currentRepoUrl.replace(/\/$/, '') + '/compare/' + base + '...' + head + '?expand=1&title=' + title + '&body=' + body;
    window.open(url, '_blank');
}

function testGithubConnection(btn) {
    var orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang kiểm tra...';
    btn.disabled = true;

    fetch('{{ route('projects.development.github.check', $project) }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        btn.disabled = false;
        btn.innerHTML = orig;
        var badge = document.getElementById('githubLiveMetaBadge');
        if (data.ok) {
            if (badge) {
                badge.style.display = 'inline-flex';
                badge.innerHTML = '<i class="fa fa-star"></i> ' + data.stars + ' Stars &bull; ' + (data.private ? 'Private Repo' : 'Public Repo') + ' &bull; RateLimit: ' + (data.rate_remaining ?? 'OK');
            }
            alert('✅ ' + data.message + '\n• Repo: ' + data.full_name + ' (' + (data.private ? 'Private' : 'Public') + ')\n• Default Branch: ' + data.default_branch + '\n• Open Issues/PRs: ' + data.open_issues);
        } else {
            alert('⚠️ ' + (data.message || 'Không thể kết nối GitHub API.'));
        }
    })
    .catch(function () {
        btn.disabled = false;
        btn.innerHTML = orig;
        alert('⚠️ Lỗi mạng khi kiểm tra kết nối GitHub.');
    });
}

function filterGitLinks(type) {
    currentGitTypeFilter = type;
    document.querySelectorAll('.tp-dev-filter-btn').forEach(function (btn) {
        if (btn.getAttribute('data-filter') === type) {
            btn.classList.add('is-active');
        } else {
            btn.classList.remove('is-active');
        }
    });
    applyGitFilters();
}

function applyGitFilters() {
    var taskFilter = (document.getElementById('gitTaskFilter') ? document.getElementById('gitTaskFilter').value : '');
    var searchQuery = (document.getElementById('gitSearchInput') ? document.getElementById('gitSearchInput').value : '').toLowerCase().trim();

    document.querySelectorAll('#gitLinksTable tbody tr').forEach(function (row) {
        var rType = row.getAttribute('data-type') || '';
        var rTask = row.getAttribute('data-task-id') || '';
        var rSearch = row.getAttribute('data-search') || '';

        var matchType = (currentGitTypeFilter === 'all' || rType === currentGitTypeFilter);
        var matchTask = (!taskFilter || rTask === taskFilter);
        var matchSearch = (!searchQuery || rSearch.indexOf(searchQuery) !== -1);

        row.style.display = (matchType && matchTask && matchSearch) ? '' : 'none';
    });
}

function copyTextToClipboard(text, btn) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text);
    } else {
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }
    if (btn) {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-check"></i> Đã chép';
        setTimeout(function () { btn.innerHTML = orig; }, 1600);
    }
}
</script>
@endsection
