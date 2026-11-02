@php
    $currentRoute = request()->route()?->getName() ?? '';
    $currentTab = $active ?? match(true) {
        str_starts_with($currentRoute, 'sprints.') => 'sprints',
        $currentRoute === 'projects.board' => 'board',
        str_starts_with($currentRoute, 'projects.retrospectives') => 'retrospectives',
        str_starts_with($currentRoute, 'projects.tql') => 'tql',
        str_starts_with($currentRoute, 'projects.custom-fields') => 'custom_fields',
        $currentRoute === 'projects.show' => 'overview',
        str_starts_with($currentRoute, 'projects.members') => 'members',
        str_starts_with($currentRoute, 'projects.roles') || str_starts_with($currentRoute, 'projects.permissions') => 'roles',
        $currentRoute === 'projects.timeline' => 'timeline',
        $currentRoute === 'projects.roadmap' => 'roadmap',
        str_starts_with($currentRoute, 'projects.documents') => 'documents',
        str_starts_with($currentRoute, 'projects.development') => 'development',
        str_starts_with($currentRoute, 'projects.reports') => 'reports',
        str_starts_with($currentRoute, 'meetings.') => 'meetings',
        default => '',
    };
@endphp

<nav class="pd-project-nav mb-4" aria-label="Điều hướng dự án">
    <a href="{{ route('projects.index') }}" class="pd-nav-item" title="Quay lại danh sách dự án">
        <i class="fa fa-folder-open-o"></i>
        <span>Dự án</span>
    </a>
    <a href="{{ route('sprints.index', $project) }}" class="pd-nav-item {{ $currentTab === 'sprints' ? 'active' : '' }}">
        <i class="fa fa-bolt"></i>
        <span>Backlog / Sprint</span>
    </a>
    <a href="{{ route('projects.board', $project) }}" class="pd-nav-item {{ $currentTab === 'board' ? 'active' : '' }}">
        <i class="fa fa-columns"></i>
        <span>Bảng (Board)</span>
    </a>
    <a href="{{ route('projects.retrospectives.index', $project) }}" class="pd-nav-item {{ $currentTab === 'retrospectives' ? 'active' : '' }}">
        <i class="fa fa-retweet"></i>
        <span>Retrospective</span>
    </a>
    <a href="{{ route('projects.tql.index', $project) }}" class="pd-nav-item {{ $currentTab === 'tql' ? 'active' : '' }}">
        <i class="fa fa-terminal"></i>
        <span>TQL Search</span>
    </a>
    <a href="{{ route('projects.custom-fields.index', $project) }}" class="pd-nav-item {{ $currentTab === 'custom_fields' ? 'active' : '' }}">
        <i class="fa fa-sliders"></i>
        <span>Trường tùy chỉnh</span>
    </a>
    <a href="{{ route('projects.show', $project) }}" class="pd-nav-item {{ $currentTab === 'overview' ? 'active' : '' }}">
        <i class="fa fa-th-large"></i>
        <span>Tổng quan</span>
    </a>
    <a href="{{ route('projects.members.index', $project) }}" class="pd-nav-item {{ $currentTab === 'members' ? 'active' : '' }}">
        <i class="fa fa-users"></i>
        <span>Thành viên</span>
    </a>
    <a href="{{ route('projects.roles.index', $project) }}" class="pd-nav-item {{ $currentTab === 'roles' ? 'active' : '' }}">
        <i class="fa fa-shield"></i>
        <span>Vai trò &amp; Quyền</span>
    </a>
    <a href="{{ route('projects.timeline', $project) }}" class="pd-nav-item {{ $currentTab === 'timeline' ? 'active' : '' }}">
        <i class="fa fa-calendar"></i>
        <span>Timeline</span>
    </a>
    <a href="{{ route('projects.roadmap', $project) }}" class="pd-nav-item {{ $currentTab === 'roadmap' ? 'active' : '' }}">
        <i class="fa fa-calendar-check-o"></i>
        <span>Lịch biểu</span>
    </a>
    <a href="{{ route('projects.documents.index', $project) }}" class="pd-nav-item {{ $currentTab === 'documents' ? 'active' : '' }}">
        <i class="fa fa-file-text-o"></i>
        <span>Tài liệu</span>
    </a>
    <a href="{{ route('projects.development.index', $project) }}" class="pd-nav-item {{ $currentTab === 'development' ? 'active' : '' }}">
        <i class="fa fa-code-fork"></i>
        <span>Development (Git)</span>
    </a>
    @if(\Illuminate\Support\Facades\Route::has('projects.reports.show'))
    <a href="{{ route('projects.reports.show', $project) }}" class="pd-nav-item {{ $currentTab === 'reports' ? 'active' : '' }}">
        <i class="fa fa-bar-chart"></i>
        <span>Báo cáo</span>
    </a>
    @endif
    <a href="{{ route('meetings.index', $project) }}" class="pd-nav-item {{ $currentTab === 'meetings' ? 'active' : '' }}">
        <i class="fa fa-video-camera"></i>
        <span>Phòng họp</span>
    </a>
</nav>
