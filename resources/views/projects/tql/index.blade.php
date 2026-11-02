@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/project-dashboard-luxury.css') }}?v={{ file_exists(public_path('css/project-dashboard-luxury.css')) ? filemtime(public_path('css/project-dashboard-luxury.css')) : time() }}" rel="stylesheet">
<style>
    .tql-container {
        font-family: inherit;
        color: #1e293b;
    }
    .filter-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 18px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    }
    .filter-label {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 5px;
        display: block;
    }
    .tql-editor-input {
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.6;
        color: #0f172a;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 12px 14px;
        width: 100%;
        resize: vertical;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .tql-editor-input:focus {
        border-color: #3b82f6;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .tql-preset-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
    }
    .tql-preset-pill:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .tql-table-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
        margin-top: 20px;
    }
    .mode-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.15s ease;
    }
</style>
@endpush

@section('content')
<div class="col-xs-12 col-md-12 p-0 tql-container">
    <!-- Header -->
    <div class="pd-header mb-3" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div class="pd-header-left">
            <div class="pd-breadcrumb" style="margin-bottom: 6px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                @if($project)
                    <a href="{{ route('projects.index') }}" style="color: #64748b; text-decoration: none;">Dự án</a>
                    <span>/</span>
                    <a href="{{ route('projects.show', $project) }}" style="color: #64748b; text-decoration: none;">{{ $project->name }}</a>
                    <span>/</span>
                    <span style="color: #0f172a; font-weight: 700;">Bộ lọc công việc</span>
                @else
                    <a href="{{ route('home') }}" style="color: #64748b; text-decoration: none;">Trang chủ</a>
                    <span>/</span>
                    <span style="color: #0f172a; font-weight: 700;">Bộ lọc công việc</span>
                @endif
            </div>
            <div class="pd-title-row" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <h1 class="pd-title" style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">
                    <i class="fa fa-filter text-primary me-2"></i>Bộ lọc công việc (Task Filter)
                </h1>

                <!-- Mode Switcher -->
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ $project ? route('projects.tql.index', array_merge(['project' => $project->id], request()->except('mode'), ['mode' => 'simple'])) : route('tql.index', array_merge(request()->except('mode'), ['mode' => 'simple'])) }}" 
                       class="mode-toggle-btn {{ $mode === 'simple' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        <i class="fa fa-sliders"></i>Bộ lọc trực quan
                    </a>
                    <a href="{{ $project ? route('projects.tql.index', array_merge(['project' => $project->id], request()->except('mode'), ['mode' => 'tql'])) : route('tql.index', array_merge(request()->except('mode'), ['mode' => 'tql'])) }}" 
                       class="mode-toggle-btn {{ $mode === 'tql' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        <i class="fa fa-terminal"></i>Cú pháp TQL nâng cao
                    </a>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @php
                $exportUrl = $project 
                    ? route('projects.tql.export', array_merge(['project' => $project->id], request()->all())) 
                    : route('tql.export', request()->all());
            @endphp
            <a href="{{ $exportUrl }}" class="btn btn-sm btn-outline-secondary" title="Xuất kết quả tìm kiếm ra file CSV">
                <i class="fa fa-download me-1"></i>Xuất CSV
            </a>
            @if($mode === 'tql')
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#saveFilterModal">
                    <i class="fa fa-bookmark-o me-1"></i>Lưu bộ lọc
                </button>
            @endif
        </div>
    </div>

    @if($project)
        <!-- Unified Project Sub-Nav Bar -->
        @include('projects.partials.nav', ['project' => $project, 'active' => 'tql'])
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- FILTER AREA -->
    @if($mode === 'simple')
        <!-- 1. BỘ LỌC ĐƠN GIẢN (TRỰC QUAN, DỄ DÙNG) -->
        <div class="filter-card mb-4">
            <form method="GET" action="{{ $project ? route('projects.tql.index', $project) : route('tql.index') }}">
                <input type="hidden" name="mode" value="simple">
                @if(!$project && request()->filled('project_id'))
                    <input type="hidden" name="project_id" value="{{ request('project_id') }}">
                @endif

                <div class="row g-3 align-items-end">
                    <!-- Search Text -->
                    <div class="col-md-3 col-sm-6">
                        <label class="filter-label">
                            <i class="fa fa-search me-1 text-primary"></i>Từ khóa tìm kiếm
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm theo tên, mã TP-xxx..." value="{{ $search }}">
                    </div>

                    <!-- Status -->
                    <div class="col-md-2 col-sm-6">
                        <label class="filter-label">Trạng thái</label>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tất cả trạng thái</option>
                            <option value="open" {{ $status === 'open' ? 'selected' : '' }}>⚡ Chưa xong (Open tasks)</option>
                            <option value="todo" {{ $status === 'todo' ? 'selected' : '' }}>To Do (Cần làm)</option>
                            <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress (Đang làm)</option>
                            <option value="in_review" {{ $status === 'in_review' ? 'selected' : '' }}>In Review (Chờ duyệt)</option>
                            <option value="done" {{ $status === 'done' ? 'selected' : '' }}>Done (Hoàn thành)</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div class="col-md-2 col-sm-6">
                        <label class="filter-label">Độ ưu tiên</label>
                        <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $priority === 'all' ? 'selected' : '' }}>Tất cả độ ưu tiên</option>
                            <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>🔥 Khẩn cấp (Urgent)</option>
                            <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>⚠️ Cao (High)</option>
                            <option value="medium" {{ $priority === 'medium' ? 'selected' : '' }}>🔵 Trung bình (Medium)</option>
                            <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>⚪ Thấp (Low)</option>
                        </select>
                    </div>

                    <!-- Assignee -->
                    <div class="col-md-2 col-sm-6">
                        <label class="filter-label">Người phụ trách</label>
                        <select name="assignee" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $assignee === 'all' ? 'selected' : '' }}>Tất cả thành viên</option>
                            <option value="me" {{ $assignee === 'me' ? 'selected' : '' }}>👤 Gán cho tôi</option>
                            <option value="unassigned" {{ $assignee === 'unassigned' ? 'selected' : '' }}>❓ Chưa có người nhận</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" {{ $assignee == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Issue Type -->
                    <div class="col-md-2 col-sm-6">
                        <label class="filter-label">Loại công việc</label>
                        <select name="issue_type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $issueType === 'all' ? 'selected' : '' }}>Tất cả các loại</option>
                            <option value="task" {{ $issueType === 'task' ? 'selected' : '' }}>Task</option>
                            <option value="bug" {{ $issueType === 'bug' ? 'selected' : '' }}>Bug</option>
                            <option value="story" {{ $issueType === 'story' ? 'selected' : '' }}>Story</option>
                            <option value="epic" {{ $issueType === 'epic' ? 'selected' : '' }}>Epic</option>
                        </select>
                    </div>

                    <!-- Action buttons -->
                    <div class="col-md-1 col-sm-6 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100" title="Áp dụng lọc">
                            <i class="fa fa-filter"></i>
                        </button>
                        <a href="{{ $project ? route('projects.tql.index', ['project' => $project->id, 'mode' => 'simple']) : route('tql.index', ['mode' => 'simple']) }}" class="btn btn-sm btn-outline-secondary" title="Đặt lại bộ lọc">
                            <i class="fa fa-refresh"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Filters Pills -->
                <div class="d-flex align-items-center gap-2 flex-wrap mt-3 pt-3 border-top">
                    <span class="text-muted small fw-bold">Lọc nhanh:</span>
                    <a href="{{ $project ? route('projects.tql.index', ['project' => $project->id, 'mode' => 'simple', 'assignee' => 'me', 'status' => 'open']) : route('tql.index', ['mode' => 'simple', 'assignee' => 'me', 'status' => 'open']) }}" class="tql-preset-pill">
                        📌 Việc của tôi (Chưa xong)
                    </a>
                    <a href="{{ $project ? route('projects.tql.index', ['project' => $project->id, 'mode' => 'simple', 'issue_type' => 'bug', 'status' => 'open']) : route('tql.index', ['mode' => 'simple', 'issue_type' => 'bug', 'status' => 'open']) }}" class="tql-preset-pill">
                        🐛 Các Bug còn mở
                    </a>
                    <a href="{{ $project ? route('projects.tql.index', ['project' => $project->id, 'mode' => 'simple', 'priority' => 'urgent', 'status' => 'open']) : route('tql.index', ['mode' => 'simple', 'priority' => 'urgent', 'status' => 'open']) }}" class="tql-preset-pill">
                        🔥 Việc khẩn cấp
                    </a>
                    <a href="{{ $project ? route('projects.tql.index', ['project' => $project->id, 'mode' => 'simple', 'assignee' => 'unassigned']) : route('tql.index', ['mode' => 'simple', 'assignee' => 'unassigned']) }}" class="tql-preset-pill">
                        ❓ Chưa gán ai
                    </a>
                    <a href="{{ $project ? route('projects.tql.index', ['project' => $project->id, 'mode' => 'simple', 'status' => 'done']) : route('tql.index', ['mode' => 'simple', 'status' => 'done']) }}" class="tql-preset-pill">
                        ✅ Đã hoàn thành
                    </a>
                </div>
            </form>
        </div>
    @else
        <!-- 2. BỘ LỌC CÚ PHÁP TQL (NÂNG CAO) -->
        <div class="filter-card mb-4">
            <form method="GET" action="{{ $project ? route('projects.tql.index', $project) : route('tql.index') }}" id="tql-form">
                <input type="hidden" name="mode" value="tql">
                @if(!$project && request()->filled('project_id'))
                    <input type="hidden" name="project_id" value="{{ request('project_id') }}">
                @endif

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="fw-bold small text-muted">
                        <i class="fa fa-code me-1 text-primary"></i>Nhập câu truy vấn TQL:
                    </label>
                    <span class="text-muted small">Cú pháp: <code>field = "value" AND priority in ("high", "urgent")</code></span>
                </div>

                <textarea name="q" id="tql-input" class="tql-editor-input" rows="2" placeholder="VD: status in (&quot;todo&quot;, &quot;in_progress&quot;) AND priority = &quot;high&quot; ORDER BY priority DESC">{{ $q }}</textarea>

                @if($error)
                    <div class="alert alert-danger py-2 px-3 mt-2 mb-0 d-flex align-items-center gap-2" style="font-size: 13px; border-radius: 8px;">
                        <i class="fa fa-exclamation-circle text-danger fa-lg"></i>
                        <div><strong>Lỗi cú pháp TQL:</strong> {{ $error }}</div>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted small fw-bold">Mẫu TQL:</span>
                        <a href="javascript:void(0)" class="tql-preset-pill" onclick="applyPreset('assignee = currentUser() AND status != &quot;done&quot; ORDER BY priority DESC')">
                            📌 Việc của tôi
                        </a>
                        <a href="javascript:void(0)" class="tql-preset-pill" onclick="applyPreset('issue_type = &quot;bug&quot; AND sprint in openSprints()')">
                            🐛 Bug Sprint hiện tại
                        </a>
                        <a href="javascript:void(0)" class="tql-preset-pill" onclick="applyPreset('priority in (&quot;high&quot;, &quot;urgent&quot;) AND status != &quot;done&quot;')">
                            🚨 Khẩn cấp
                        </a>
                        <a href="javascript:void(0)" class="tql-preset-pill" onclick="applyPreset('assignee is null')">
                            👤 Chưa gán ai
                        </a>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('tql-input').value='';">
                            Xóa
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">
                            <i class="fa fa-search me-1"></i>Tìm kiếm TQL
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- Results Table & Sidebar -->
    <div class="row">
        @if($mode === 'tql')
        <!-- Sidebar for Saved Filters & Syntax Helper (chỉ hiện ở TQL mode) -->
        <div class="col-md-3 col-sm-12">
            <!-- Saved Filters -->
            <div class="filter-card mb-3 p-2">
                <div class="fw-bold small text-muted p-2 border-bottom">
                    <i class="fa fa-bookmark text-primary me-1"></i>Bộ lọc đã lưu
                </div>
                <div class="p-1">
                    @forelse($savedFilters as $sf)
                        <div class="d-flex justify-content-between align-items-center p-2 rounded hover-bg" style="font-size: 13px;">
                            <a href="{{ $project ? route('projects.tql.index', ['project' => $project->id, 'mode' => 'tql', 'q' => $sf->query]) : route('tql.index', ['mode' => 'tql', 'q' => $sf->query]) }}" class="text-truncate flex-grow-1 text-decoration-none text-dark" title="{{ $sf->query }}">
                                {{ $sf->name }}
                            </a>
                            @if($sf->user_id === auth()->id())
                                <form action="{{ route('tql.filters.destroy', $sf) }}" method="POST" onsubmit="return confirm('Xóa bộ lọc này?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-link text-muted p-0 ms-1" title="Xóa bộ lọc"><i class="fa fa-times"></i></button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted small text-center py-2">Chưa lưu bộ lọc nào.</div>
                    @endforelse
                </div>
            </div>

            <!-- Syntax Helper -->
            <div class="filter-card p-3" style="font-size: 12px; background: #f8fafc;">
                <h4 style="font-size: 13px; font-weight: 700; margin: 0 0 10px 0; color: #0f172a;">
                    <i class="fa fa-book text-primary me-1"></i>Cẩm nang TQL
                </h4>
                <div class="mb-2">
                    <strong>Trường:</strong> <code>status</code>, <code>priority</code>, <code>assignee</code>, <code>issue_type</code>, <code>sprint</code>, <code>summary</code>, <code>cf[key]</code>
                </div>
                <div class="mb-2">
                    <strong>Toán tử:</strong> <code>=</code>, <code>!=</code>, <code>IN</code>, <code>NOT IN</code>, <code>&gt;</code>, <code>&lt;</code>, <code>~</code>, <code>IS NULL</code>
                </div>
                <div>
                    <strong>Hàm:</strong> <code>currentUser()</code>, <code>openSprints()</code>, <code>today()</code>
                </div>
            </div>
        </div>
        @endif

        <!-- Results Table (Full 12 cols in Simple mode, 9 cols in TQL mode) -->
        <div class="{{ $mode === 'tql' ? 'col-md-9' : 'col-md-12' }} col-sm-12">
            <div class="tql-table-card mt-0">
                <div class="cf-card-header d-flex justify-content-between align-items-center py-2 px-3">
                    <span class="fw-bold small text-muted">
                        Tìm thấy: <strong>{{ $tasks ? $tasks->total() : 0 }}</strong> công việc
                    </span>
                    @if($tasks && $tasks->total() > 0)
                        <span class="text-muted small">Trang {{ $tasks->currentPage() }} / {{ $tasks->lastPage() }}</span>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 110px;">Mã (Key)</th>
                                <th style="width: 80px;">Loại</th>
                                <th>Tiêu đề công việc</th>
                                <th>Dự án</th>
                                <th>Người phụ trách</th>
                                <th style="width: 95px;">Độ ưu tiên</th>
                                <th style="width: 105px;">Trạng thái</th>
                                <th style="width: 70px;">Points</th>
                                <th>Hạn chót</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($tasks && $tasks->count() > 0)
                                @foreach($tasks as $t)
                                    @php
                                        $priorityColors = [
                                            'urgent' => 'danger',
                                            'high' => 'warning',
                                            'medium' => 'primary',
                                            'low' => 'secondary',
                                        ];
                                        $isDone = in_array(strtolower((string)$t->status), ['done', 'closed', 'completed', 'resolved']);
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('tasks.show', $t) }}" class="fw-bold text-decoration-none" style="color: #2563eb;">
                                                {{ $t->issue_key ?? 'TP-'.$t->id }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-muted border">{{ strtoupper($t->issue_type ?? 'TASK') }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('tasks.show', $t) }}" class="text-dark fw-bold text-decoration-none {{ $isDone ? 'text-decoration-line-through text-muted' : '' }}">
                                                {{ $t->name }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($t->project)
                                                <a href="{{ route('projects.show', $t->project) }}" class="text-muted text-decoration-none small">
                                                    {{ $t->project->name }}
                                                </a>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($t->assignee)
                                                <span class="small text-dark" title="{{ $t->assignee->name }}">
                                                    <i class="fa fa-user me-1 text-muted"></i>{{ $t->assignee->name }}
                                                </span>
                                            @else
                                                <span class="text-muted small font-italic">Chưa gán</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $priorityColors[strtolower((string)$t->priority)] ?? 'secondary' }}">
                                                {{ strtoupper($t->priority ?? 'MEDIUM') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $isDone ? 'bg-success' : 'bg-light text-dark border' }}">
                                                {{ strtoupper($t->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $t->story_points ?? '0' }}</span>
                                        </td>
                                        <td class="small {{ $t->due_date && $t->due_date->isPast() && !$isDone ? 'text-danger fw-bold' : 'text-muted' }}">
                                            {{ $t->due_date ? $t->due_date->format('d/m/Y') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fa fa-search fa-2x mb-2 d-block opacity-50"></i>
                                        Không tìm thấy công việc nào phù hợp với bộ lọc hiện tại.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if($tasks && $tasks->hasPages())
                    <div class="p-3 border-top d-flex justify-content-end">
                        {{ $tasks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Save Filter (Dành cho TQL) -->
@if($mode === 'tql')
<div class="modal fade" id="saveFilterModal" tabindex="-1" aria-labelledby="saveFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('tql.filters.store') }}" method="POST">
            @csrf
            @if($project)
                <input type="hidden" name="project_id" value="{{ $project->id }}">
            @endif
            <input type="hidden" name="query" value="{{ $q }}">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="saveFilterModalLabel">
                        <i class="fa fa-bookmark text-primary me-2"></i>Lưu câu truy vấn thành Bộ lọc
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Tên bộ lọc <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Các bug nghiêm trọng của tôi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Câu truy vấn TQL</label>
                        <textarea class="form-control" rows="2" readonly style="font-family: monospace; background: #f8fafc;">{{ $q }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Mô tả ngắn</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Ghi chú thêm về bộ lọc này..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-primary">Lưu bộ lọc</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
function applyPreset(query) {
    var input = document.getElementById('tql-input');
    if (input) {
        input.value = query;
        document.getElementById('tql-form').submit();
    }
}
</script>
@endpush
@endsection
