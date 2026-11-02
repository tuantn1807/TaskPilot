@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/project-dashboard-luxury.css') }}?v={{ file_exists(public_path('css/project-dashboard-luxury.css')) ? filemtime(public_path('css/project-dashboard-luxury.css')) : time() }}" rel="stylesheet">
<style>
    .retro-container {
        font-family: inherit;
        color: #1e293b;
    }
    .retro-stats-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .retro-stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .retro-board-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 24px;
    }
    @media (max-width: 992px) {
        .retro-board-grid {
            grid-template-columns: 1fr;
        }
    }
    .retro-column {
        background: #f8fafc;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        min-height: 580px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .retro-col-header {
        padding: 16px 18px;
        border-bottom: 2px solid transparent;
        border-top-left-radius: 14px;
        border-top-right-radius: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .retro-col-header.col-went-well {
        background: #f0fdf4;
        border-bottom-color: #86efac;
    }
    .retro-col-header.col-to-improve {
        background: #fffbeb;
        border-bottom-color: #fde68a;
    }
    .retro-col-header.col-action-item {
        background: #eff6ff;
        border-bottom-color: #bfdbfe;
    }
    .retro-col-title {
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .retro-card-list {
        padding: 16px;
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .retro-card-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        position: relative;
    }
    .retro-card-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }
    .retro-card-body {
        font-size: 14px;
        line-height: 1.55;
        color: #334155;
        white-space: pre-wrap;
        word-break: break-word;
        margin-bottom: 12px;
    }
    .retro-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 10px;
        border-top: 1px dashed #f1f5f9;
        font-size: 12px;
    }
    .retro-author-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .retro-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 11px;
    }
    .retro-avatar.anonymous {
        background: #475569;
        color: #ffffff;
    }
    .retro-avatar.user {
        background: #2563eb;
        color: #ffffff;
    }
    .retro-vote-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .retro-vote-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }
    .retro-vote-btn.voted {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #2563eb;
    }
    .retro-vote-btn.voted i {
        color: #2563eb;
    }
    .retro-add-box {
        padding: 14px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
    }
    .retro-add-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 13px;
        resize: vertical;
        min-height: 64px;
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.15s;
    }
    .retro-add-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }
    .retro-toggle-anon {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #475569;
        cursor: pointer;
        user-select: none;
        margin: 0;
    }
    .retro-toggle-anon input[type="checkbox"] {
        cursor: pointer;
        accent-color: #475569;
        width: 15px;
        height: 15px;
    }
    .retro-badge-anon {
        background: #f1f5f9;
        color: #475569;
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
</style>
@endpush

@section('content')
<div class="col-xs-12 col-md-12 p-0 retro-container">
    <!-- Project Header -->
    <div class="pd-header mb-3" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div class="pd-header-left">
            <div class="pd-breadcrumb" style="margin-bottom: 6px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <a href="{{ route('projects.index') }}" style="color: #64748b; text-decoration: none;">Dự án</a>
                <span>/</span>
                <a href="{{ route('projects.show', $project) }}" style="color: #64748b; text-decoration: none;">{{ $project->name }}</a>
                <span>/</span>
                <span style="color: #0f172a; font-weight: 700;">Scrum Retrospective</span>
            </div>
            <div class="pd-title-row" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <h1 class="pd-title" style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">
                    <i class="fa fa-retweet text-primary me-2"></i>Sprint Retrospective
                </h1>
                <span class="pd-project-key">{{ $project->project_code ?? 'PROJ' }}</span>
                <span class="badge bg-primary text-white" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">Agile Ceremony</span>
            </div>
            <p style="margin: 6px 0 0 0; color: #64748b; font-size: 13px;">
                Không gian phản hồi cởi mở, <strong>hỗ trợ đăng ẩn danh</strong> và <strong>bình chọn (Voting)</strong> để tìm ra các vấn đề trọng tâm cần cải tiến.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Sprint Filter -->
            <form method="GET" action="{{ route('projects.retrospectives.index', $project) }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <select name="sprint_id" class="form-select form-select-sm" style="font-size: 13px; font-weight: 600; min-width: 180px; height: 36px;" onchange="this.form.submit()">
                    <option value="all" {{ is_null($selectedSprintId) ? 'selected' : '' }}>📌 Toàn dự án (Chung)</option>
                    @foreach($sprints as $sprint)
                        <option value="{{ $sprint->id }}" {{ $selectedSprintId == $sprint->id ? 'selected' : '' }}>
                            ⚡ {{ $sprint->name }} {{ $sprint->status === 'active' ? '(Đang chạy)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Sort Toggle -->
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('projects.retrospectives.index', array_merge(['project' => $project->id], request()->except('sort'), ['sort' => 'votes'])) }}" 
                   class="btn {{ $sort === 'votes' ? 'btn-primary' : 'btn-outline-secondary' }}" title="Sắp xếp theo số vote cao nhất">
                    <i class="fa fa-fire me-1"></i>Nhiều vote nhất
                </a>
                <a href="{{ route('projects.retrospectives.index', array_merge(['project' => $project->id], request()->except('sort'), ['sort' => 'newest'])) }}" 
                   class="btn {{ $sort === 'newest' ? 'btn-primary' : 'btn-outline-secondary' }}" title="Sắp xếp theo thời gian đăng mới nhất">
                    <i class="fa fa-clock-o me-1"></i>Mới nhất
                </a>
            </div>
        </div>
    </div>

    <!-- Unified Project Sub-Nav Bar -->
    @include('projects.partials.nav', ['project' => $project, 'active' => 'retrospectives'])

    <!-- Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xs-12 col-sm-4">
            <div class="retro-stats-card">
                <div class="retro-stats-icon" style="background: #e0f2fe; color: #0284c7;">
                    <i class="fa fa-lightbulb-o"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: #0f172a;">{{ $totalCards }}</div>
                    <div style="font-size: 13px; color: #64748b;">Tổng số ý kiến đóng góp</div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4">
            <div class="retro-stats-card">
                <div class="retro-stats-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="fa fa-thumbs-o-up"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: #0f172a;">{{ $totalVotes }}</div>
                    <div style="font-size: 13px; color: #64748b;">Tổng lượt bình chọn (Votes)</div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4">
            <div class="retro-stats-card">
                <div class="retro-stats-icon" style="background: #dcfce7; color: #16a34a;">
                    <i class="fa fa-users"></i>
                </div>
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: #0f172a;">{{ $participantsCount }}</div>
                    <div style="font-size: 13px; color: #64748b;">Thành viên tham gia</div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa fa-info-circle me-1"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 3-Column Retrospective Board Grid -->
    <div class="retro-board-grid">

        <!-- Column 1: Went Well -->
        <div class="retro-column">
            <div class="retro-col-header col-went-well">
                <span class="retro-col-title text-success">
                    <i class="fa fa-smile-o"></i> Làm tốt (What went well)
                </span>
                <span class="badge bg-success rounded-pill">{{ count($wentWellCards) }}</span>
            </div>

            <!-- Quick Add Form -->
            <div class="retro-add-box">
                <form action="{{ route('projects.retrospectives.cards.store', [$project, $retrospective]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="column_type" value="went_well">
                    <textarea name="content" class="retro-add-input" placeholder="Điểm sáng, thành tích, điều nhóm đã làm rất tốt..." required></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                        <label class="retro-toggle-anon" title="Khi bật, các thành viên khác sẽ không biết ai đã gửi ý kiến này">
                            <input type="checkbox" name="is_anonymous" value="1">
                            <span>🕵️ Đăng ẩn danh</span>
                        </label>
                        <button type="submit" class="btn btn-sm btn-success px-3">
                            <i class="fa fa-plus me-1"></i>Đăng thẻ
                        </button>
                    </div>
                </form>
            </div>

            <!-- Cards List -->
            <div class="retro-card-list">
                @forelse($wentWellCards as $card)
                    <div class="retro-card-item" id="retro-card-{{ $card->id }}">
                        <div class="retro-card-body">{{ $card->content }}</div>
                        <div class="retro-card-footer">
                            <div class="retro-author-wrap">
                                @if($card->is_anonymous)
                                    <div class="retro-avatar anonymous" title="Ý kiến được đăng ở chế độ ẩn danh">🕵️</div>
                                    <span class="retro-badge-anon">
                                        Ẩn danh @if($card->user_id === auth()->id()) <small class="text-primary">(Bạn)</small> @endif
                                    </span>
                                @else
                                    <div class="retro-avatar user">
                                        {{ strtoupper(substr($card->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span style="font-weight: 600; color: #475569;">{{ $card->user->name ?? 'Thành viên' }}</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="retro-vote-btn {{ $card->isVotedBy(auth()->id()) ? 'voted' : '' }}" onclick="toggleVote({{ $card->id }})" id="vote-btn-{{ $card->id }}" title="Bình chọn cho ý kiến này">
                                    <i class="fa fa-thumbs-up"></i>
                                    <span id="vote-count-{{ $card->id }}">{{ $card->votes_count }}</span>
                                </button>
                                @if($card->user_id === auth()->id() || $project->user_id === auth()->id())
                                    <form action="{{ route('projects.retrospectives.cards.destroy', [$project, $card]) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa ý kiến này?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-muted p-0" title="Xóa thẻ"><i class="fa fa-trash-o"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">
                        <i class="fa fa-leaf fa-2x mb-2 d-block text-muted opacity-50"></i>
                        Chưa có ý kiến nào. Hãy là người đầu tiên chia sẻ điểm tốt!
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Column 2: To Improve -->
        <div class="retro-column">
            <div class="retro-col-header col-to-improve">
                <span class="retro-col-title text-warning" style="color: #d97706 !important;">
                    <i class="fa fa-meh-o"></i> Cần cải thiện (To improve)
                </span>
                <span class="badge bg-warning text-dark rounded-pill">{{ count($toImproveCards) }}</span>
            </div>

            <!-- Quick Add Form -->
            <div class="retro-add-box">
                <form action="{{ route('projects.retrospectives.cards.store', [$project, $retrospective]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="column_type" value="to_improve">
                    <textarea name="content" class="retro-add-input" placeholder="Khó khăn, nút thắt cổ chai, điều làm bạn thất vọng trong Sprint..." required></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                        <label class="retro-toggle-anon" title="Khi bật, các thành viên khác sẽ không biết ai đã gửi ý kiến này">
                            <input type="checkbox" name="is_anonymous" value="1" checked>
                            <span>🕵️ Đăng ẩn danh</span>
                        </label>
                        <button type="submit" class="btn btn-sm btn-warning px-3" style="color: #1e293b; font-weight: 700;">
                            <i class="fa fa-plus me-1"></i>Đăng thẻ
                        </button>
                    </div>
                </form>
            </div>

            <!-- Cards List -->
            <div class="retro-card-list">
                @forelse($toImproveCards as $card)
                    <div class="retro-card-item" id="retro-card-{{ $card->id }}">
                        <div class="retro-card-body">{{ $card->content }}</div>
                        <div class="retro-card-footer">
                            <div class="retro-author-wrap">
                                @if($card->is_anonymous)
                                    <div class="retro-avatar anonymous" title="Ý kiến được đăng ở chế độ ẩn danh">🕵️</div>
                                    <span class="retro-badge-anon">
                                        Ẩn danh @if($card->user_id === auth()->id()) <small class="text-primary">(Bạn)</small> @endif
                                    </span>
                                @else
                                    <div class="retro-avatar user">
                                        {{ strtoupper(substr($card->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span style="font-weight: 600; color: #475569;">{{ $card->user->name ?? 'Thành viên' }}</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="retro-vote-btn {{ $card->isVotedBy(auth()->id()) ? 'voted' : '' }}" onclick="toggleVote({{ $card->id }})" id="vote-btn-{{ $card->id }}" title="Bình chọn cho ý kiến này">
                                    <i class="fa fa-thumbs-up"></i>
                                    <span id="vote-count-{{ $card->id }}">{{ $card->votes_count }}</span>
                                </button>
                                @if($card->user_id === auth()->id() || $project->user_id === auth()->id())
                                    <form action="{{ route('projects.retrospectives.cards.destroy', [$project, $card]) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa ý kiến này?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-muted p-0" title="Xóa thẻ"><i class="fa fa-trash-o"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">
                        <i class="fa fa-exclamation-circle fa-2x mb-2 d-block text-muted opacity-50"></i>
                        Không có phàn nàn nào! Mọi thứ diễn ra thật trơn tru.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Column 3: Action Items -->
        <div class="retro-column">
            <div class="retro-col-header col-action-item">
                <span class="retro-col-title text-primary">
                    <i class="fa fa-check-square-o"></i> Hành động (Action items)
                </span>
                <span class="badge bg-primary rounded-pill">{{ count($actionItemCards) }}</span>
            </div>

            <!-- Quick Add Form -->
            <div class="retro-add-box">
                <form action="{{ route('projects.retrospectives.cards.store', [$project, $retrospective]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="column_type" value="action_item">
                    <textarea name="content" class="retro-add-input" placeholder="Giải pháp cụ thể, hành động cam kết khắc phục ở Sprint tới..." required></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                        <label class="retro-toggle-anon" title="Khi bật, các thành viên khác sẽ không biết ai đã gửi ý kiến này">
                            <input type="checkbox" name="is_anonymous" value="1">
                            <span>🕵️ Đăng ẩn danh</span>
                        </label>
                        <button type="submit" class="btn btn-sm btn-primary px-3">
                            <i class="fa fa-plus me-1"></i>Đăng hành động
                        </button>
                    </div>
                </form>
            </div>

            <!-- Cards List -->
            <div class="retro-card-list">
                @forelse($actionItemCards as $card)
                    <div class="retro-card-item" id="retro-card-{{ $card->id }}" style="border-left: 3px solid #3b82f6;">
                        <div class="retro-card-body">{{ $card->content }}</div>

                        <!-- Converted Task Indicator or Convert Button -->
                        <div class="mb-2">
                            @if($card->converted_task_id && $card->convertedTask)
                                <a href="{{ route('tasks.show', $card->convertedTask) }}" class="badge bg-success text-decoration-none d-inline-flex align-items-center gap-1" target="_blank" style="font-size: 11px; padding: 5px 8px;">
                                    <i class="fa fa-check-circle"></i> Đã tạo Task: {{ $card->convertedTask->issue_key ?? 'TP-'.$card->convertedTask->id }}
                                    <i class="fa fa-external-link small ms-1"></i>
                                </a>
                            @else
                                <form action="{{ route('projects.retrospectives.cards.convert', [$project, $card]) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-primary" style="font-size: 11px; padding: 2px 8px;" title="Chuyển hành động này thành một Task thực tế trong dự án">
                                        <i class="fa fa-bolt me-1"></i>Chuyển thành Task
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="retro-card-footer">
                            <div class="retro-author-wrap">
                                @if($card->is_anonymous)
                                    <div class="retro-avatar anonymous" title="Ý kiến được đăng ở chế độ ẩn danh">🕵️</div>
                                    <span class="retro-badge-anon">
                                        Ẩn danh @if($card->user_id === auth()->id()) <small class="text-primary">(Bạn)</small> @endif
                                    </span>
                                @else
                                    <div class="retro-avatar user">
                                        {{ strtoupper(substr($card->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span style="font-weight: 600; color: #475569;">{{ $card->user->name ?? 'Thành viên' }}</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="retro-vote-btn {{ $card->isVotedBy(auth()->id()) ? 'voted' : '' }}" onclick="toggleVote({{ $card->id }})" id="vote-btn-{{ $card->id }}" title="Bình chọn cho hành động này">
                                    <i class="fa fa-thumbs-up"></i>
                                    <span id="vote-count-{{ $card->id }}">{{ $card->votes_count }}</span>
                                </button>
                                @if($card->user_id === auth()->id() || $project->user_id === auth()->id())
                                    <form action="{{ route('projects.retrospectives.cards.destroy', [$project, $card]) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa ý kiến này?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-muted p-0" title="Xóa thẻ"><i class="fa fa-trash-o"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">
                        <i class="fa fa-tasks fa-2x mb-2 d-block text-muted opacity-50"></i>
                        Chưa có hành động cụ thể nào. Hãy biến các góp ý thành hành động!
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function toggleVote(cardId) {
    const btn = document.getElementById('vote-btn-' + cardId);
    const countEl = document.getElementById('vote-count-' + cardId);
    if (!btn || !countEl) return;

    btn.disabled = true;

    fetch(`{{ url('projects/' . $project->id . '/retrospectives/cards') }}/${cardId}/vote`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            countEl.textContent = data.votes_count;
            if (data.voted) {
                btn.classList.add('voted');
            } else {
                btn.classList.remove('voted');
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        console.error('Lỗi khi bình chọn:', err);
    });
}
</script>
@endpush
@endsection

