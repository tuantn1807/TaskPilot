@extends('layouts.meeting')

@section('content')
<meta name="meeting-tasks-url" content="{{ route('meetings.tasks', $meeting) }}">
<meta name="meeting-token-url" content="{{ route('meetings.token', $meeting) }}">
<meta name="meeting-messages-url" content="{{ route('meetings.messages', $meeting) }}">
<meta name="meeting-message-url" content="{{ route('meetings.messages.store', $meeting) }}">
<meta name="meeting-note-url" content="{{ route('meetings.note', $meeting) }}">
<meta name="meeting-archive-url" content="{{ route('meetings.archive', $meeting) }}">
<meta name="meeting-file-prefix" content="{{ url('meetings/' . $meeting->id . '/file') }}">
<meta name="meeting-members" content='@json($meeting->members->map(fn ($member) => ['id' => $member->id, 'name' => $member->name]))'>
<meta name="current-user-id" content="{{ auth()->id() }}">
<meta name="meeting-platform" content="{{ $meeting->platform ?? 'taskpilot' }}">

<div class="tp-meet-app" id="meeting-room">
    <!-- Top Bar -->
    <header class="tp-meet-topbar">
        <div class="tp-meet-brand">
            <span class="tp-meet-logo">
                <i class="{{ $meeting->platform_info['icon'] ?? 'fa fa-video-camera' }}"></i>
            </span>
            <strong>TaskPilot <em>Meet</em></strong>
            <span class="tp-meet-divider"></span>
            <span class="tp-meet-title">{{ $meeting->title }}</span>
            <span class="tp-platform-badge platform-{{ $meeting->platform ?? 'taskpilot' }}" style="margin-left: 8px;">
                {{ $meeting->platform_label }}
            </span>
        </div>
        <div class="tp-meet-status">
            <span class="tp-live-dot"></span>
            <span id="connection-state">
                @if($meeting->isBuiltin())
                    Đang kết nối...
                @elseif($meeting->isOffline())
                    Họp trực tiếp
                @else
                    {{ $meeting->platform_label }}
                @endif
            </span>
        </div>
        <div class="tp-meet-top-actions">
            @if($meeting->project)
                <a href="{{ route('meetings.index', $meeting->project) }}" title="Danh sách cuộc họp" class="text-white" style="margin-right: 8px;">
                    <i class="fa fa-arrow-left"></i> Dự án
                </a>
            @endif
            <a href="{{ route('meetings.archive', $meeting) }}" title="Mở archive / Biên bản cuộc họp">
                <i class="fa fa-archive"></i> Biên bản
            </a>
            <button id="leave-room" class="tp-leave-button">
                <i class="fa fa-sign-out"></i> Rời phòng
            </button>
        </div>
    </header>

    <main class="tp-meet-main">
        <!-- Main Stage Section -->
        <section class="tp-stage">
            @if($meeting->isBuiltin())
                {{-- Built-in LiveKit WebRTC Grid --}}
                <div id="video-grid" class="tp-video-grid">
                    <div class="tp-video-placeholder">
                        <i class="fa fa-video-camera"></i>
                        <span>Đang mở camera và microphone...</span>
                    </div>
                </div>

                <div class="tp-stage-toolbar">
                    <button id="toggle-mic" class="tp-round-control" title="Bật/tắt microphone">
                        <i class="fa fa-microphone"></i><span>Mic</span>
                    </button>
                    <button id="toggle-camera" class="tp-round-control" title="Bật/tắt camera">
                        <i class="fa fa-video-camera"></i><span>Camera</span>
                    </button>
                    <button id="share-screen" class="tp-round-control" title="Chia sẻ màn hình">
                        <i class="fa fa-desktop"></i><span>Chia sẻ</span>
                    </button>
                    <button id="raise-hand" class="tp-round-control" title="Giơ tay">
                        <i class="fa fa-hand-paper-o"></i><span>Giơ tay</span>
                    </button>
                    <button id="reaction-toggle" class="tp-round-control" title="Bày tỏ cảm xúc">
                        <i class="fa fa-smile-o"></i><span>Cảm xúc</span>
                    </button>
                    <div id="reaction-picker" class="tp-reaction-picker" hidden>
                        <button data-reaction="👍">👍</button>
                        <button data-reaction="❤️">❤️</button>
                        <button data-reaction="😂">😂</button>
                        <button data-reaction="👏">👏</button>
                        <button data-reaction="🎉">🎉</button>
                        <button data-reaction="😮">😮</button>
                        <button data-reaction="😢">😢</button>
                        <button data-reaction="🔥">🔥</button>
                        <button data-reaction="✅">✅</button>
                    </div>
                </div>
                <div id="floating-reactions" class="tp-floating-reactions"></div>
            @elseif($meeting->isExternalOnline())
                {{-- External Online Meeting Stage (Google Meet, Zoom, Teams, Other) --}}
                <div class="tp-external-stage">
                    <div class="tp-external-card">
                        <div class="tp-external-icon" style="background: {{ $meeting->platform_info['bg'] ?? '#f1f5f9' }}; color: {{ $meeting->platform_info['color'] ?? '#2563eb' }};">
                            <i class="{{ $meeting->platform_info['icon'] ?? 'fa fa-video-camera' }}"></i>
                        </div>
                        <h2 class="tp-external-title">{{ $meeting->title }}</h2>
                        <div class="tp-external-meta">
                            <span><i class="fa fa-calendar-check-o me-1"></i> {{ $meeting->starts_at ? $meeting->starts_at->format('d/m/Y H:i') : 'Linh hoạt' }}</span>
                            <span class="mx-2">·</span>
                            <span class="tp-platform-badge platform-{{ $meeting->platform }}">{{ $meeting->platform_label }}</span>
                        </div>

                        @if($meeting->meeting_link)
                            <a href="{{ $meeting->meeting_link }}" target="_blank" rel="noopener noreferrer" class="tp-btn-join-external" style="background: {{ $meeting->platform_info['color'] ?? '#2563eb' }};">
                                <i class="fa fa-external-link"></i> Tham gia trên {{ $meeting->platform_label }}
                            </a>

                            <div class="tp-info-box-pill">
                                <span class="text-truncate" style="color: #cbd5e1; max-width: 320px;" title="{{ $meeting->meeting_link }}">
                                    <i class="fa fa-link me-1"></i> {{ $meeting->meeting_link }}
                                </span>
                                <button type="button" class="tp-copy-btn" data-copy-target="{{ $meeting->meeting_link }}">
                                    <i class="fa fa-copy"></i> Sao chép
                                </button>
                            </div>
                        @else
                            <div class="alert alert-warning text-dark text-start mb-3" style="border-radius: 8px; font-size: 13px;">
                                <i class="fa fa-exclamation-triangle me-1"></i> Chưa có đường dẫn cuộc họp. Người tổ chức vui lòng bổ sung liên kết.
                            </div>
                        @endif

                        @if($meeting->passcode)
                            <div class="tp-info-box-pill">
                                <span style="color: #cbd5e1;">
                                    <i class="fa fa-key me-1"></i> <strong>Passcode / ID:</strong> {{ $meeting->passcode }}
                                </span>
                                <button type="button" class="tp-copy-btn" data-copy-target="{{ $meeting->passcode }}">
                                    <i class="fa fa-copy"></i> Sao chép
                                </button>
                            </div>
                        @endif

                        @if($meeting->description)
                            <div class="text-start mt-3 p-3" style="background: rgba(15,23,42,0.5); border-radius: 8px; font-size: 13px; color: #cbd5e1;">
                                <strong style="color: #ffffff; display: block; margin-bottom: 4px;">Nội dung / Mục tiêu:</strong>
                                {!! nl2br(e($meeting->description)) !!}
                            </div>
                        @endif

                        <div class="mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #94a3b8;">
                            <i class="fa fa-info-circle me-1"></i>
                            Bạn có thể mở cuộc họp ở tab mới hoặc app {{ $meeting->platform_label }}, và đồng thời sử dụng bảng điều khiển bên phải của TaskPilot để ghi chú riêng, trao đổi tin nhắn, ghi biên bản và tạo Task trực tiếp cho dự án.
                        </div>
                    </div>
                </div>
            @else
                {{-- Offline Meeting Stage --}}
                <div class="tp-external-stage">
                    <div class="tp-external-card">
                        <div class="tp-external-icon" style="background: #fce8e6; color: #c5221f;">
                            <i class="fa fa-map-marker"></i>
                        </div>
                        <h2 class="tp-external-title">{{ $meeting->title }}</h2>
                        <div class="tp-external-meta">
                            <span><i class="fa fa-calendar me-1"></i> {{ $meeting->starts_at ? $meeting->starts_at->format('d/m/Y H:i') : 'Linh hoạt' }}</span>
                            <span class="mx-2">·</span>
                            <span class="tp-platform-badge platform-offline">Trực tiếp (Offline)</span>
                        </div>

                        <div class="tp-info-box-pill" style="padding: 14px; background: rgba(234,67,53,0.1); border-color: rgba(234,67,53,0.3);">
                            <span style="color: #ffffff; font-size: 14px;">
                                <i class="fa fa-map-pin text-danger me-2"></i>
                                <strong>Địa điểm:</strong> {{ $meeting->location ?? 'Chưa xác định phòng họp' }}
                            </span>
                        </div>

                        @if($meeting->description)
                            <div class="text-start mt-3 p-3" style="background: rgba(15,23,42,0.5); border-radius: 8px; font-size: 13px; color: #cbd5e1;">
                                <strong style="color: #ffffff; display: block; margin-bottom: 4px;">Nội dung chuẩn bị:</strong>
                                {!! nl2br(e($meeting->description)) !!}
                            </div>
                        @endif

                        <div class="mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #94a3b8;">
                            <i class="fa fa-pencil me-1"></i>
                            Không gian hỗ trợ ghi chép biên bản cuộc họp trực tiếp, lưu trữ quyết định và chuyển đổi ý kiến thảo luận thành Task công việc trong dự án.
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <!-- Sidebar Section -->
        <aside class="tp-room-sidebar">
            <div class="tp-sidebar-tabs">
                <button class="active" data-panel="chat-panel">Chat</button>
                <button data-panel="people-panel">Thành viên <span id="participant-count"></span></button>
                <button data-panel="note-panel">Ghi chú riêng</button>
            </div>

            <!-- Chat Panel -->
            <div id="chat-panel" class="tp-side-panel">
                <div id="chat-messages" class="tp-chat-messages"></div>
                <form id="chat-form" class="tp-chat-form" enctype="multipart/form-data">
                    <textarea id="chat-body" name="body" rows="2" placeholder="Viết tin nhắn, @mention..."></textarea>
                    <input id="chat-file" name="files[]" type="file" multiple hidden>
                    <div>
                        <button type="button" id="emoji-toggle" class="tp-chat-icon" title="Chèn biểu tượng"><i class="fa fa-smile-o"></i></button>
                        <button type="button" id="attach-file" class="tp-chat-icon" title="Đính kèm nhiều file"><i class="fa fa-paperclip"></i></button>
                        <button type="submit" class="tp-send-button" title="Gửi tin nhắn"><i class="fa fa-send"></i></button>
                    </div>
                    <div id="emoji-picker" class="tp-emoji-picker" hidden>
                        <button type="button">😀</button>
                        <button type="button">😂</button>
                        <button type="button">😍</button>
                        <button type="button">👍</button>
                        <button type="button">👏</button>
                        <button type="button">🎉</button>
                        <button type="button">🔥</button>
                        <button type="button">✅</button>
                        <button type="button">🙏</button>
                        <button type="button">😮</button>
                    </div>
                    <div id="file-preview" class="tp-file-preview"></div>
                </form>
            </div>

            <!-- People Panel -->
            <div id="people-panel" class="tp-side-panel d-none">
                <div id="participants-list"></div>
            </div>

            <!-- Note Panel -->
            <div id="note-panel" class="tp-side-panel d-none">
                <p class="small text-muted mb-2">Chỉ bạn có thể xem ghi chú này.</p>
                <textarea id="private-note" class="form-control" rows="12" placeholder="Ghi chép nhanh ý kiến, nội dung trao đổi..."></textarea>
                <button id="save-note" class="btn btn-primary mt-2 w-100" style="border-radius: 8px;">
                    <i class="fa fa-save me-1"></i> Lưu ghi chú
                </button>
            </div>
        </aside>
    </main>
</div>

<div id="task-picker" class="tp-task-picker" hidden>
    <input id="task-search" type="search" placeholder="Tìm task trong dự án...">
    <div id="task-results"></div>
</div>

@if($meeting->isBuiltin())
<script src="https://unpkg.com/livekit-client@2.5.0/dist/livekit-client.umd.js"></script>
@endif
<script src="{{ asset('js/meetings.js') }}"></script>
@endsection
