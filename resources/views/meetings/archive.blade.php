@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/meeting-ui.css') }}?v={{ file_exists(public_path('css/meeting-ui.css')) ? filemtime(public_path('css/meeting-ui.css')) : time() }}" rel="stylesheet">
@endpush

@section('content')
<div class="tp-meeting-shell">
    <div class="tp-meeting-heading" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 4px;">
                <a href="{{ route('meetings.index', $meeting->project) }}" style="color: #64748b; text-decoration: none;">&larr; Danh sách cuộc họp</a> · TỔNG HỢP & BIÊN BẢN
            </div>
            <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 800; color: #0f172a;">{{ $meeting->title }}</h1>
            <p style="margin: 0; color: #64748b; font-size: 13.5px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span>{{ $meeting->project->name }}</span>
                <span>·</span>
                <span>{{ $meeting->starts_at ? $meeting->starts_at->format('d/m/Y H:i') : 'Chưa đặt giờ' }}</span>
                <span>·</span>
                <span class="tp-platform-badge platform-{{ $meeting->platform ?? 'taskpilot' }}">
                    {{ $meeting->platform_label }}
                </span>
                @if($meeting->isOffline() && $meeting->location)
                    <span>·</span>
                    <span style="color: #b91c1c; font-weight: 600;"><i class="fa fa-map-marker me-1"></i> {{ $meeting->location }}</span>
                @elseif($meeting->isExternalOnline() && $meeting->meeting_link)
                    <span>·</span>
                    <a href="{{ $meeting->meeting_link }}" target="_blank" rel="noopener noreferrer" style="color: #0284c7; font-weight: 600;">
                        <i class="fa fa-external-link me-1"></i> Link cuộc họp
                    </a>
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            @if($meeting->isExternalOnline() && $meeting->meeting_link)
                <a href="{{ $meeting->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-success" style="border-radius: 8px; font-weight: 600;">
                    <i class="fa fa-external-link me-1"></i> Vào link họp
                </a>
            @endif
            <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-primary tp-btn-primary" style="border-radius: 8px; font-weight: 600;">
                <i class="fa fa-comments-o me-1"></i> Mở không gian họp
            </a>
        </div>
    </div>

    <div class="tp-archive-grid">
        <section class="tp-archive-section">
            <h2><i class="fa fa-file-text-o"></i> Biên bản cuộc họp</h2>
            @if($meeting->minutes)
                <h3>Nội dung</h3>
                <p>{!! nl2br(e($meeting->minutes->content)) !!}</p>
                <h3>Quyết định</h3>
                <p>{!! nl2br(e($meeting->minutes->decisions)) !!}</p>
                <h3>Action items</h3>
                @foreach($meeting->minutes->actions as $action)
                    <div class="tp-action-item">
                        <strong>{{ $action->title }}</strong>
                        <small>{{ $action->assignee?->name ?? 'Chưa giao' }} · {{ $action->due_date?->format('d/m/Y') ?? 'Chưa có deadline' }}</small>
                    </div>
                @endforeach
            @else
                <p class="text-muted">Chưa có biên bản được ghi nhận.</p>
            @endif
        </section>

        <section class="tp-archive-section">
            <h2><i class="fa fa-video-camera"></i> Video & tài liệu</h2>
            @forelse($meeting->recordings as $recording)
                <a class="tp-file-row" href="{{ route('meetings.file', [$meeting, 'path' => $recording->path]) }}">
                    <i class="fa fa-file-video-o"></i>{{ $recording->original_name }}
                </a>
            @empty
                <p class="text-muted">Chưa có recording.</p>
            @endforelse

            <h2 class="mt-4"><i class="fa fa-comments"></i> Chat thảo luận</h2>
            @forelse($meeting->messages as $message)
                <div class="tp-archive-message">
                    <strong>{{ $message->user->name }}</strong>
                    <span>{{ $message->created_at->format('H:i d/m') }}</span>
                    <p>{{ $message->body }}</p>
                    @if($message->attachment_path)
                        <a href="{{ route('meetings.file', [$meeting, 'path' => $message->attachment_path]) }}">
                            <i class="fa fa-paperclip me-1"></i>{{ $message->attachment_name }}
                        </a>
                    @endif
                </div>
            @empty
                <p class="text-muted">Chưa có tin nhắn trong cuộc họp.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
