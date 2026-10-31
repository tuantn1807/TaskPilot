@extends('layouts.app')
@section('content')
<div class="team-index-wrapper" style="width:100%;padding:24px 28px;box-sizing:border-box;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <h1 class="tp-team-page-title" style="margin:0;font-size:28px;font-weight:700;line-height:1.25;">Đội nhóm</h1>
            <p class="tp-team-page-desc" style="margin:5px 0 0;font-size:14px;color:#6b778c;">Quản lý đội nhóm và thành viên trong hệ thống</p>
        </div>
        <a href="{{ route('teams.create') }}" style="display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 16px;background:#0052cc;color:#fff;border:1px solid #0052cc;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;">
            <i class="fa fa-plus"></i> Tạo đội nhóm
        </a>
    </div>

    <div style="display:grid;grid-template-columns:minmax(0,3fr) minmax(260px,1fr);gap:24px;align-items:start;">
        <div>
            <!-- 4-column Stat Grid -->
            <div class="tp-team-stat-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px;">
                <div class="tp-team-card" style="display:flex;align-items:center;gap:14px;padding:18px;border-radius:8px;">
                    <div style="width:44px;height:44px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#e9f2ff;color:#0052cc;border-radius:8px;font-size:18px;"><i class="fa fa-users"></i></div>
                    <div>
                        <div class="tp-stat-sub" style="font-size:12.5px;color:#6b778c;">Tổng đội nhóm</div>
                        <strong class="tp-stat-val" style="display:block;margin-top:2px;font-size:22px;line-height:1.2;">{{ $teams->count() }}</strong>
                    </div>
                </div>

                <div class="tp-team-card" style="display:flex;align-items:center;gap:14px;padding:18px;border-radius:8px;">
                    <div style="width:44px;height:44px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#e3fcef;color:#00875a;border-radius:8px;font-size:18px;"><i class="fa fa-user"></i></div>
                    <div>
                        <div class="tp-stat-sub" style="font-size:12.5px;color:#6b778c;">Tổng thành viên</div>
                        <strong class="tp-stat-val" style="display:block;margin-top:2px;font-size:22px;line-height:1.2;">{{ $teams->sum('users_count') }}</strong>
                    </div>
                </div>

                <div class="tp-team-card" style="display:flex;align-items:center;gap:14px;padding:18px;border-radius:8px;">
                    <div style="width:44px;height:44px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#eae6ff;color:#6554c0;border-radius:8px;font-size:18px;"><i class="fa fa-shield"></i></div>
                    <div>
                        <div class="tp-stat-sub" style="font-size:12.5px;color:#6b778c;">Đội bạn sở hữu</div>
                        <strong class="tp-stat-val" style="display:block;margin-top:2px;font-size:22px;line-height:1.2;">{{ $teams->where('user_id', Auth::id())->count() }}</strong>
                    </div>
                </div>

                <div class="tp-team-card" style="display:flex;align-items:center;gap:14px;padding:18px;border-radius:8px;">
                    <div style="width:44px;height:44px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#fffae6;color:#974f00;border-radius:8px;font-size:18px;"><i class="fa fa-user-plus"></i></div>
                    <div>
                        <div class="tp-stat-sub" style="font-size:12.5px;color:#6b778c;">Đội đang tham gia</div>
                        <strong class="tp-stat-val" style="display:block;margin-top:2px;font-size:22px;line-height:1.2;">{{ $teams->count() }}</strong>
                    </div>
                </div>
            </div>

            <!-- Teams List Card -->
            <div class="tp-team-card" style="border-radius:8px;overflow:hidden;">
                <div class="tp-team-card-header" style="display:flex;align-items:center;justify-content:space-between;padding:18px;border-bottom:1px solid #ebecf0;">
                    <div>
                        <h2 class="tp-team-section-title" style="margin:0 0 4px;font-size:18px;font-weight:600;">Danh sách đội nhóm</h2>
                        <p class="tp-team-page-desc" style="margin:0;font-size:13px;color:#6b778c;">Các đội nhóm bạn đang tham gia</p>
                    </div>
                    <span style="padding:5px 10px;color:#0747a6;background:#deebff;border-radius:4px;font-size:12px;font-weight:600;">{{ $teams->count() }} đội nhóm</span>
                </div>

                <div style="padding:18px;">
                   @forelse($teams as $team)
                    <div class="tp-team-item-card" style="padding:20px;border-radius:8px;margin-bottom:14px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
                            <div style="display:flex;align-items:center;gap:14px;min-width:0;">
                                <div style="width:46px;height:46px;flex:0 0 46px;display:flex;align-items:center;justify-content:center;color:#0052cc;background:#e9f2ff;border-radius:8px;font-size:18px;">
                                    <i class="fa fa-users"></i>
                                </div>

                                <div style="min-width:0;">
                                    <h3 class="tp-team-item-title" title="{{ $team->name }}" style="max-width:100%;margin:0 0 7px;overflow:hidden;font-size:17px;font-weight:600;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ $team->name }}
                                    </h3>

                                    <div class="tp-team-meta-row" style="display:flex;align-items:center;gap:18px;font-size:13px;color:#6b778c;">
                                        <span>
                                            <i class="fa fa-users" style="margin-right:6px;color:#0052cc;"></i>
                                            {{ $team->users_count }} thành viên
                                        </span>
                                        <span>
                                            <i class="fa fa-clock-o" style="margin-right:6px;color:#00875a;"></i>
                                            Cập nhật {{ $team->updated_at ? $team->updated_at->diffForHumans() : $team->created_at->diffForHumans() }}
                                        </span>
                                        <span>
                                            <i class="fa fa-calendar" style="margin-right:6px;color:#6554c0;"></i>
                                            Tạo {{ $team->created_at->format('d/m/Y') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex;align-items:center;gap:8px;flex:0 0 auto;">
                                <a href="{{ route('teams.show', $team) }}" class="btn-team-action" style="display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 12px;color:#0052cc;background:#fff;border:1px solid #0052cc;border-radius:6px;text-decoration:none;font-size:13px;font-weight:500;">
                                    <i class="fa fa-eye"></i> Xem chi tiết
                                </a>

                                <a href="{{ route('teams.edit', $team) }}" class="btn-team-action" style="display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 12px;color:#42526e;background:#fff;border:1px solid #dfe1e6;border-radius:6px;text-decoration:none;font-size:13px;font-weight:500;">
                                    <i class="fa fa-edit"></i> Sửa
                                </a>

                                <form action="{{ route('teams.destroy', $team) }}" method="POST" style="margin:0;" onsubmit="return confirm('Bạn có chắc muốn xóa đội nhóm này không?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-team-action" style="display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 12px;color:#de350b;background:#fff;border:1px solid #de350b;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer;">
                                        <i class="fa fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="text-align:center;padding:50px 20px;">
                        <div style="width:64px;height:64px;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;color:#6b778c;background:#f4f5f7;border-radius:50%;font-size:24px;">
                            <i class="fa fa-users"></i>
                        </div>
                        <h3 class="tp-team-item-title" style="margin:0 0 5px;font-size:18px;">Chưa có đội nhóm</h3>
                        <p class="tp-team-page-desc" style="margin:0 0 18px;font-size:14px;color:#6b778c;">Tạo đội nhóm đầu tiên để bắt đầu quản lý thành viên.</p>
                        <a href="{{ route('teams.create') }}" style="display:inline-flex;align-items:center;gap:6px;height:40px;padding:0 16px;background:#0052cc;color:#fff;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;">
                            <i class="fa fa-plus"></i> Tạo đội nhóm
                        </a>
                    </div>
                @endforelse
                </div>
            </div>
        </div>

        <div>
            @include('partials.right_sidebar')
        </div>
    </div>
</div>

<style>
.tp-team-card {
    background: #ffffff;
    border: 1px solid #ebecf0;
    box-shadow: 0 1px 3px rgba(9, 30, 66, 0.08);
}
.tp-team-item-card {
    background: #ffffff;
    border: 1px solid #ebecf0;
    transition: all 0.15s ease;
}
.tp-team-item-card:hover {
    border-color: #b3d4ff;
    box-shadow: 0 2px 8px rgba(9, 30, 66, 0.08);
}
.tp-team-page-title, .tp-stat-val, .tp-team-section-title, .tp-team-item-title {
    color: #172b4d;
}

/* Dark Mode Overrides for Teams Index */
.tp-theme-dark .tp-team-card,
.tp-theme-dark .tp-team-item-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2) !important;
}
.tp-theme-dark .tp-team-card-header {
    border-bottom-color: #334155 !important;
}
.tp-theme-dark .tp-team-page-title,
.tp-theme-dark .tp-stat-val,
.tp-theme-dark .tp-team-section-title,
.tp-theme-dark .tp-team-item-title {
    color: #f8fafc !important;
}
.tp-theme-dark .tp-team-page-desc,
.tp-theme-dark .tp-stat-sub,
.tp-theme-dark .tp-team-meta-row {
    color: #94a3b8 !important;
}
.tp-theme-dark .btn-team-action {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}
.tp-theme-dark .btn-team-action:hover {
    background: #1e293b !important;
    border-color: #475569 !important;
}
</style>
@endsection
