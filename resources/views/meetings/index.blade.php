@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/project-dashboard-luxury.css') }}?v={{ file_exists(public_path('css/project-dashboard-luxury.css')) ? filemtime(public_path('css/project-dashboard-luxury.css')) : time() }}" rel="stylesheet">
<link href="{{ asset('css/meeting-ui.css') }}?v={{ file_exists(public_path('css/meeting-ui.css')) ? filemtime(public_path('css/meeting-ui.css')) : time() }}" rel="stylesheet">
<style>
.tp-platform-filter-btn {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.tp-platform-filter-btn:hover {
    background: #f8fafc;
    color: #1e293b;
    border-color: #cbd5e1;
}
.tp-platform-filter-btn.active {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 2px 6px rgba(37,99,235,0.25);
}
.tp-platform-filter-btn.active .badge {
    background: rgba(255,255,255,0.25) !important;
    color: #ffffff !important;
}
.tp-meeting-card-link {
    transition: transform 0.15s, box-shadow 0.15s;
}
.tp-meeting-card-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}
</style>
@endpush

@section('content')
<div class="col-xs-12 col-md-12 p-0">
    <div class="tp-meeting-shell">
        <!-- Project Header -->
        <div class="pd-header mb-3" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div class="pd-header-left">
                <div class="pd-breadcrumb" style="margin-bottom: 6px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                    <a href="{{ route('projects.index') }}" style="color: #64748b; text-decoration: none;">Dự án</a>
                    <span>/</span>
                    <a href="{{ route('projects.show', $project) }}" style="color: #64748b; text-decoration: none;">{{ $project->name }}</a>
                    <span>/</span>
                    <span style="color: #0f172a; font-weight: 700;">Phòng họp đa nền tảng</span>
                </div>
                <div class="pd-title-row" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h1 class="pd-title" style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">{{ $project->name }} / Cuộc họp dự án</h1>
                    <span class="pd-project-key">{{ $project->project_code ?? 'PROJ' }}</span>
                    <span class="pd-project-status">{{ strtoupper($project->status ?? 'active') }}</span>
                </div>
                <p class="text-muted" style="margin: 6px 0 0 0; color: #64748b; font-size: 13.5px;">
                    Hỗ trợ linh hoạt nhiều hình thức: <strong>Google Meet</strong>, <strong>Zoom</strong>, <strong>Microsoft Teams</strong>, <strong>TaskPilot WebRTC</strong> hoặc <strong>Họp trực tiếp tại văn phòng</strong> kèm ghi chép biên bản & giao việc tức thì.
                </p>
            </div>
            <div>
                <button type="button" class="btn btn-primary tp-btn-primary" data-toggle="modal" data-target="#newMeeting" data-bs-toggle="modal" data-bs-target="#newMeeting" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                    <i class="fa fa-calendar-plus-o"></i>
                    <span>Tạo cuộc họp mới</span>
                </button>
            </div>
        </div>

        <!-- Unified Project Sub-Nav Bar -->
        @include('projects.partials.nav', ['project' => $project, 'active' => 'meetings'])

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top: 16px; border-radius: 8px; font-size: 13.5px;">
                <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="border:none;background:none;float:right;font-size:18px;">&times;</button>
            </div>
        @endif

        <!-- Filter tabs by platform -->
        <div style="margin-top: 20px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="tp-platform-filter-btn active" data-filter="all">
                Tất cả <span class="badge" style="background:#e2e8f0; color:#334155; font-size:11px; margin-left:4px;">{{ $meetings->count() }}</span>
            </button>
            <button type="button" class="tp-platform-filter-btn" data-filter="google_meet">
                <i class="fa fa-video-camera" style="color: #0f9d58;"></i> Google Meet
                <span class="badge" style="background:#e2e8f0; color:#334155; font-size:11px; margin-left:4px;">{{ $meetings->where('platform', 'google_meet')->count() }}</span>
            </button>
            <button type="button" class="tp-platform-filter-btn" data-filter="zoom">
                <i class="fa fa-video-camera" style="color: #2d8cff;"></i> Zoom
                <span class="badge" style="background:#e2e8f0; color:#334155; font-size:11px; margin-left:4px;">{{ $meetings->where('platform', 'zoom')->count() }}</span>
            </button>
            <button type="button" class="tp-platform-filter-btn" data-filter="teams">
                <i class="fa fa-users" style="color: #5059c9;"></i> MS Teams
                <span class="badge" style="background:#e2e8f0; color:#334155; font-size:11px; margin-left:4px;">{{ $meetings->where('platform', 'teams')->count() }}</span>
            </button>
            <button type="button" class="tp-platform-filter-btn" data-filter="taskpilot">
                <i class="fa fa-bolt" style="color: #f59e0b;"></i> TaskPilot Meet
                <span class="badge" style="background:#e2e8f0; color:#334155; font-size:11px; margin-left:4px;">{{ $meetings->where('platform', 'taskpilot')->count() }}</span>
            </button>
            <button type="button" class="tp-platform-filter-btn" data-filter="offline">
                <i class="fa fa-map-marker" style="color: #ea4335;"></i> Trực tiếp
                <span class="badge" style="background:#e2e8f0; color:#334155; font-size:11px; margin-left:4px;">{{ $meetings->where('platform', 'offline')->count() }}</span>
            </button>
        </div>

        <!-- Meetings List -->
        <div class="tp-meeting-list" style="margin-top: 16px;">
            @forelse($meetings as $meeting)
                <div class="tp-meeting-row tp-meeting-card-link meeting-item-box" data-platform="{{ $meeting->platform ?? 'taskpilot' }}" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; text-decoration: none; cursor: pointer;" onclick="window.location='{{ route('meetings.show', $meeting) }}'">
                    <!-- Icon / Badge -->
                    <span class="tp-meeting-icon" style="background: {{ $meeting->platform_info['bg'] ?? '#f1f5f9' }}; color: {{ $meeting->platform_info['color'] ?? '#2563eb' }}; width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 18px; flex-shrink: 0;">
                        <i class="{{ $meeting->platform_info['icon'] ?? 'fa fa-video-camera' }}"></i>
                    </span>

                    <!-- Content -->
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                            <strong style="font-size: 15.5px; color: #0f172a; font-weight: 700;">{{ $meeting->title }}</strong>
                            <span class="tp-platform-badge platform-{{ $meeting->platform ?? 'taskpilot' }}">
                                {{ $meeting->platform_label }}
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 13px; color: #64748b;">
                            <span>
                                <i class="fa fa-clock-o me-1"></i>
                                {{ $meeting->starts_at ? $meeting->starts_at->format('d/m/Y H:i') : 'Thời gian linh hoạt' }}
                            </span>
                            <span>·</span>
                            <span>
                                <i class="fa fa-users me-1"></i>
                                {{ $meeting->members->count() }} thành viên
                            </span>

                            @if($meeting->isOffline() && $meeting->location)
                                <span>·</span>
                                <span style="color: #b91c1c; font-weight: 600;">
                                    <i class="fa fa-map-marker me-1"></i> {{ $meeting->location }}
                                </span>
                            @elseif($meeting->isExternalOnline() && $meeting->meeting_link)
                                <span>·</span>
                                <span class="text-truncate" style="max-width: 260px; color: #0284c7;">
                                    <i class="fa fa-link me-1"></i> {{ $meeting->meeting_link }}
                                </span>
                            @endif

                            @if($meeting->passcode)
                                <span>·</span>
                                <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 11.5px; color: #334155;">
                                    ID/Pass: {{ $meeting->passcode }}
                                </span>
                            @endif
                        </div>

                        @if($meeting->description)
                            <div style="font-size: 12.5px; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ \Illuminate\Support\Str::limit($meeting->description, 90) }}
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;" onclick="event.stopPropagation();">
                        @if($meeting->isExternalOnline() && $meeting->meeting_link)
                            <a href="{{ $meeting->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success" style="border-radius: 6px; font-weight: 600; font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(16,185,129,0.2);">
                                <i class="fa fa-external-link"></i>
                                <span>Vào họp ngay</span>
                            </a>
                        @elseif($meeting->isBuiltin())
                            <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-sm btn-primary tp-btn-primary" style="border-radius: 6px; font-weight: 600; font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fa fa-video-camera"></i>
                                <span>Vào phòng</span>
                            </a>
                        @endif

                        <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-sm btn-default" style="border-radius: 6px; font-weight: 600; font-size: 12px; padding: 6px 12px; border: 1px solid #cbd5e1; background: #ffffff;" title="Mở không gian ghi chú, chat và biên bản cuộc họp">
                            <i class="fa fa-file-text-o me-1"></i> Không gian họp
                        </a>

                        <a href="{{ route('meetings.archive', $meeting) }}" class="btn btn-sm btn-default" style="border-radius: 6px; font-size: 12px; padding: 6px 9px; border: 1px solid #cbd5e1; background: #ffffff;" title="Xem tổng kết & biên bản">
                            <i class="fa fa-archive text-muted"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="tp-empty-state" style="padding: 60px 20px; text-align: center;">
                    <i class="fa fa-comments-o" style="font-size: 44px; color: #2563eb; margin-bottom: 14px; display: block;"></i>
                    <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Chưa có cuộc họp nào</h2>
                    <p style="color: #64748b; font-size: 14px; margin-bottom: 18px;">
                        Tạo cuộc họp để kết nối nhóm qua Google Meet, Zoom, MS Teams, phòng họp nội bộ hoặc họp trực tiếp tại văn phòng.
                    </p>
                    <button type="button" class="btn btn-primary tp-btn-primary" data-toggle="modal" data-target="#newMeeting" data-bs-toggle="modal" data-bs-target="#newMeeting" style="padding: 9px 20px; border-radius: 8px; font-weight: 600;">
                        <i class="fa fa-plus me-1"></i> Tạo cuộc họp ngay
                    </button>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal Tạo Cuộc Họp Mới -->
<div class="modal fade" id="newMeeting" tabindex="-1" role="dialog" aria-labelledby="newMeetingLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <form method="POST" action="{{ route('meetings.store', $project) }}" id="formCreateMeeting">
                @csrf
                <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 22px; display: flex; justify-content: space-between; align-items: center;">
                    <h4 class="modal-title" id="newMeetingLabel" style="font-weight: 700; font-size: 17px; margin: 0; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <i class="fa fa-calendar-plus-o text-primary"></i>
                        <span>Lên lịch & Tạo cuộc họp</span>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 24px; border: none; background: transparent; cursor: pointer; color: #64748b; margin-top: -4px;">&times;</button>
                </div>

                <div class="modal-body" style="padding: 22px;">
                    <!-- Platform Selector -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: 700; font-size: 13.5px; color: #1e293b; margin-bottom: 10px; display: block;">
                            Chọn hình thức cuộc họp <span class="text-danger">*</span>
                        </label>
                        <div class="tp-platform-selector">
                            <div class="tp-platform-option is-selected" data-platform="google_meet">
                                <div class="opt-icon" style="color: #0f9d58;"><i class="fa fa-video-camera"></i></div>
                                <div class="opt-title">Google Meet</div>
                                <div class="opt-desc">Họp qua Google</div>
                            </div>
                            <div class="tp-platform-option" data-platform="zoom">
                                <div class="opt-icon" style="color: #2d8cff;"><i class="fa fa-video-camera"></i></div>
                                <div class="opt-title">Zoom</div>
                                <div class="opt-desc">Phòng họp Zoom</div>
                            </div>
                            <div class="tp-platform-option" data-platform="teams">
                                <div class="opt-icon" style="color: #5059c9;"><i class="fa fa-users"></i></div>
                                <div class="opt-title">MS Teams</div>
                                <div class="opt-desc">Họp qua Teams</div>
                            </div>
                            <div class="tp-platform-option" data-platform="taskpilot">
                                <div class="opt-icon" style="color: #f59e0b;"><i class="fa fa-bolt"></i></div>
                                <div class="opt-title">TaskPilot Meet</div>
                                <div class="opt-desc">WebRTC nội bộ</div>
                            </div>
                            <div class="tp-platform-option" data-platform="offline">
                                <div class="opt-icon" style="color: #ea4335;"><i class="fa fa-map-marker"></i></div>
                                <div class="opt-title">Trực tiếp (Offline)</div>
                                <div class="opt-desc">Tại văn phòng</div>
                            </div>
                            <div class="tp-platform-option" data-platform="other">
                                <div class="opt-icon" style="color: #64748b;"><i class="fa fa-link"></i></div>
                                <div class="opt-title">Link khác</div>
                                <div class="opt-desc">Tùy biến URL</div>
                            </div>
                        </div>
                        <input type="hidden" name="platform" id="inputMeetingPlatform" value="google_meet">
                    </div>

                    <!-- Title -->
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">
                            Tên cuộc họp <span class="text-danger">*</span>
                        </label>
                        <input name="title" required maxlength="255" class="form-control" placeholder="Ví dụ: Họp Sprint Planning, Demo sản phẩm, Thảo luận thiết kế..." style="border-radius: 8px; height: 40px; font-size: 13.5px;">
                    </div>

                    <!-- Dynamic Link Field (Google Meet, Zoom, Teams, Other) -->
                    <div id="groupMeetingLink" class="form-group" style="margin-bottom: 16px;">
                        <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">
                            <span id="linkFieldLabel">Đường dẫn Google Meet (URL)</span> <span class="text-danger" id="linkRequiredStar">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-addon" style="background: #f8fafc; border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1; border-right: none; padding: 8px 12px; display: flex; align-items: center;">
                                <i class="fa fa-link text-muted"></i>
                            </span>
                            <input name="meeting_link" id="inputMeetingLink" type="url" class="form-control" placeholder="https://meet.google.com/xxx-xxxx-xxx" style="border-radius: 0 8px 8px 0; height: 40px; font-size: 13.5px;">
                        </div>
                        <small class="text-muted" id="linkHelper" style="display: block; margin-top: 5px; font-size: 12px;">
                            Dán liên kết cuộc họp để thành viên có thể bấm vào tham gia ngay lập tức.
                        </small>
                    </div>

                    <!-- Passcode / Meeting ID (optional for online platforms) -->
                    <div id="groupPasscode" class="form-group" style="margin-bottom: 16px;">
                        <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">
                            Passcode / Meeting ID <small class="text-muted">(tùy chọn)</small>
                        </label>
                        <input name="passcode" id="inputPasscode" type="text" maxlength="100" class="form-control" placeholder="Ví dụ: Pass: 123456 / ID: 890 1234 5678" style="border-radius: 8px; height: 40px; font-size: 13.5px;">
                    </div>

                    <!-- Location Field (Offline) -->
                    <div id="groupLocation" class="form-group" style="margin-bottom: 16px; display: none;">
                        <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">
                            Địa điểm / Phòng họp trực tiếp <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-addon" style="background: #f8fafc; border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1; border-right: none; padding: 8px 12px; display: flex; align-items: center;">
                                <i class="fa fa-map-marker text-danger"></i>
                            </span>
                            <input name="location" id="inputLocation" type="text" maxlength="255" class="form-control" placeholder="Ví dụ: Phòng họp Sunflower (Tầng 3), Trụ sở chính..." style="border-radius: 0 8px 8px 0; height: 40px; font-size: 13.5px;">
                        </div>
                    </div>

                    <!-- Built-in TaskPilot Info -->
                    <div id="groupBuiltinInfo" class="alert alert-info" style="margin-bottom: 16px; display: none; border-radius: 8px; font-size: 13px; background: #f0fdf4; border-color: #bbf7d0; color: #166534;">
                        <i class="fa fa-info-circle me-1"></i>
                        Hệ thống sẽ tự động khởi tạo phòng video call HD trực tiếp qua LiveKit WebRTC trong TaskPilot (không yêu cầu cài đặt phần mềm bên ngoài).
                    </div>

                    <!-- Time & Description -->
                    <div class="row">
                        <div class="col-md-6 form-group" style="margin-bottom: 16px;">
                            <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">Thời gian bắt đầu</label>
                            <input name="starts_at" type="datetime-local" class="form-control" style="border-radius: 8px; height: 40px; font-size: 13.5px;">
                        </div>
                        <div class="col-md-6 form-group" style="margin-bottom: 16px;">
                            <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; display: block;">
                                Mục tiêu / Ghi chú chuẩn bị
                            </label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Tài liệu cần đọc trước, nội dung thảo luận..." style="border-radius: 8px; font-size: 13px; resize: vertical;"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 600; padding: 8px 16px;">Hủy</button>
                    <button type="submit" class="btn btn-primary tp-btn-primary" style="border-radius: 8px; font-weight: 700; padding: 8px 20px;">
                        <i class="fa fa-check me-1"></i> Xác nhận tạo cuộc họp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Platform switching logic
    var platformInput = document.getElementById('inputMeetingPlatform');
    var platformOptions = document.querySelectorAll('.tp-platform-option');
    var groupLink = document.getElementById('groupMeetingLink');
    var groupPasscode = document.getElementById('groupPasscode');
    var groupLocation = document.getElementById('groupLocation');
    var groupBuiltinInfo = document.getElementById('groupBuiltinInfo');
    var linkFieldLabel = document.getElementById('linkFieldLabel');
    var inputMeetingLink = document.getElementById('inputMeetingLink');
    var inputLocation = document.getElementById('inputLocation');
    var linkHelper = document.getElementById('linkHelper');

    var platformConfigs = {
        google_meet: {
            label: 'Đường dẫn Google Meet (URL)',
            placeholder: 'https://meet.google.com/xxx-xxxx-xxx',
            helper: 'Dán liên kết Google Meet để các thành viên nhấp vào tham gia ngay.',
            showLink: true,
            showLocation: false,
            showBuiltinInfo: false,
            linkRequired: true,
            locationRequired: false
        },
        zoom: {
            label: 'Đường dẫn phòng Zoom (Meeting URL)',
            placeholder: 'https://zoom.us/j/1234567890?pwd=...',
            helper: 'Dán liên kết mời tham gia phòng họp Zoom.',
            showLink: true,
            showLocation: false,
            showBuiltinInfo: false,
            linkRequired: true,
            locationRequired: false
        },
        teams: {
            label: 'Đường dẫn Microsoft Teams (Meeting URL)',
            placeholder: 'https://teams.microsoft.com/l/meetup-join/...',
            helper: 'Dán liên kết cuộc họp Microsoft Teams.',
            showLink: true,
            showLocation: false,
            showBuiltinInfo: false,
            linkRequired: true,
            locationRequired: false
        },
        taskpilot: {
            showLink: false,
            showLocation: false,
            showBuiltinInfo: true,
            linkRequired: false,
            locationRequired: false
        },
        offline: {
            showLink: false,
            showLocation: true,
            showBuiltinInfo: false,
            linkRequired: false,
            locationRequired: true
        },
        other: {
            label: 'Đường dẫn cuộc họp trực tuyến (URL)',
            placeholder: 'https://...',
            helper: 'Dán liên kết Webex, Skype, Lark hoặc nền tảng cuộc họp khác.',
            showLink: true,
            showLocation: false,
            showBuiltinInfo: false,
            linkRequired: false,
            locationRequired: false
        }
    };

    function updatePlatformUI(platformKey) {
        var cfg = platformConfigs[platformKey] || platformConfigs.other;
        platformInput.value = platformKey;

        // Visual selection
        platformOptions.forEach(function (opt) {
            opt.classList.toggle('is-selected', opt.getAttribute('data-platform') === platformKey);
        });

        // Visibility toggles
        if (cfg.showLink) {
            groupLink.style.display = 'block';
            groupPasscode.style.display = 'block';
            linkFieldLabel.textContent = cfg.label;
            inputMeetingLink.placeholder = cfg.placeholder;
            linkHelper.textContent = cfg.helper;
            inputMeetingLink.required = cfg.linkRequired;
        } else {
            groupLink.style.display = 'none';
            groupPasscode.style.display = 'none';
            inputMeetingLink.required = false;
        }

        if (cfg.showLocation) {
            groupLocation.style.display = 'block';
            inputLocation.required = cfg.locationRequired;
        } else {
            groupLocation.style.display = 'none';
            inputLocation.required = false;
        }

        groupBuiltinInfo.style.display = cfg.showBuiltinInfo ? 'block' : 'none';
    }

    platformOptions.forEach(function (option) {
        option.addEventListener('click', function () {
            var selected = this.getAttribute('data-platform');
            updatePlatformUI(selected);
        });
    });

    // Filtering by platform
    var filterBtns = document.querySelectorAll('.tp-platform-filter-btn');
    var meetingRows = document.querySelectorAll('.meeting-item-box');

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            var filter = this.getAttribute('data-filter');

            meetingRows.forEach(function (row) {
                var rowPlatform = row.getAttribute('data-platform');
                if (filter === 'all' || rowPlatform === filter) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
</script>
@endsection
