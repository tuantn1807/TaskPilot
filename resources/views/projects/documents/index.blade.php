@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/project-dashboard-luxury.css') }}?v={{ file_exists(public_path('css/project-dashboard-luxury.css')) ? filemtime(public_path('css/project-dashboard-luxury.css')) : time() }}" rel="stylesheet">
<style>
.tp-docs-page {
    width: 100%;
    padding: 0 4px;
    box-sizing: border-box;
}
.tp-docs-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.tp-docs-filters {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.tp-doc-filter-btn {
    padding: 7px 14px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.tp-doc-filter-btn:hover {
    border-color: #cbd5e1;
    color: #0f172a;
}
.tp-doc-filter-btn.is-active {
    background: #eff6ff;
    border-color: #2563eb;
    color: #2563eb;
}
.tp-docs-search {
    position: relative;
    min-width: 240px;
}
.tp-docs-search input {
    width: 100%;
    padding: 8px 12px 8px 34px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    color: #0f172a;
    background: #ffffff;
}
.tp-docs-search i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}
.tp-docs-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}
.tp-docs-table {
    width: 100%;
    border-collapse: collapse;
}
.tp-docs-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 13px 18px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}
.tp-docs-table tbody td {
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13.5px;
    color: #334155;
    vertical-align: middle;
}
.tp-docs-table tbody tr:last-child td {
    border-bottom: none;
}
.tp-docs-table tbody tr:hover {
    background: #f8fafc;
}
.tp-doc-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 800;
}
.tp-doc-icon-box.type-google_doc { background: #eff6ff; color: #2563eb; }
.tp-doc-icon-box.type-notion { background: #f1f5f9; color: #0f172a; }
.tp-doc-icon-box.type-confluence { background: #eef2ff; color: #4f46e5; }
.tp-doc-icon-box.type-file { background: #ecfdf5; color: #059669; }
.tp-doc-icon-box.type-link { background: #fffbeb; color: #d97706; }

.tp-doc-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
}
.tp-doc-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(3px);
    z-index: 1050;
    display: none;
}
.tp-doc-modal-backdrop.open { display: block; }
.tp-doc-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 92%;
    max-width: 540px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
    padding: 24px 26px;
    z-index: 1055;
    display: none;
}
.tp-doc-modal.open { display: block; }
.tp-doc-modal h2 {
    margin: 0 0 16px 0;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
}
.tp-doc-field {
    margin-bottom: 14px;
}
.tp-doc-field label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 6px;
}
.tp-doc-field input,
.tp-doc-field select,
.tp-doc-field textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13.5px;
    color: #0f172a;
    background: #ffffff;
    box-sizing: border-box;
}
.tp-doc-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
}

/* Dark Mode */
html.tp-theme-dark .tp-docs-card,
html.tp-theme-dark .tp-doc-modal,
html.tp-theme-dark .tp-doc-filter-btn,
html.tp-theme-dark .tp-docs-search input {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
html.tp-theme-dark .tp-doc-filter-btn.is-active {
    background: rgba(37, 99, 235, 0.2) !important;
    border-color: #3b82f6 !important;
    color: #60a5fa !important;
}
html.tp-theme-dark .tp-docs-table thead th {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
html.tp-theme-dark .tp-docs-table tbody td {
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}
html.tp-theme-dark .tp-docs-table tbody tr:hover {
    background: rgba(51, 65, 85, 0.4) !important;
}
html.tp-theme-dark .tp-doc-modal h2,
html.tp-theme-dark .tp-doc-field label {
    color: #f8fafc !important;
}
html.tp-theme-dark .tp-doc-field input,
html.tp-theme-dark .tp-doc-field select,
html.tp-theme-dark .tp-doc-field textarea {
    background: #0f172a !important;
    border-color: #475569 !important;
    color: #f8fafc !important;
}
</style>
@endpush

@section('content')
@php
    $typeLabels = [
        'google_doc' => 'Google Docs',
        'notion'     => 'Notion Wiki',
        'confluence' => 'Confluence',
        'file'       => 'Tệp đính kèm',
        'link'       => 'Liên kết',
    ];
@endphp

<div class="col-xs-12 col-md-12 p-0">
    <div class="tp-docs-page">
        <!-- Project Header -->
        <div class="pd-header mb-3" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div class="pd-header-left">
                <div class="pd-breadcrumb" style="margin-bottom: 6px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                    <a href="{{ route('projects.index') }}" style="color: #64748b; text-decoration: none;">Dự án</a>
                    <span>/</span>
                    <a href="{{ route('projects.show', $project) }}" style="color: #64748b; text-decoration: none;">{{ $project->name }}</a>
                    <span>/</span>
                    <span style="color: #0f172a; font-weight: 700;">Tài liệu dự án</span>
                </div>
                <div class="pd-title-row" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h1 class="pd-title" style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">{{ $project->name }}</h1>
                    <span class="pd-project-key">{{ $project->project_code ?? 'PROJ' }}</span>
                    <span class="pd-project-status">{{ strtoupper($project->status ?? 'active') }}</span>
                </div>
                <p style="margin: 6px 0 0 0; color: #64748b; font-size: 13px;">
                    Kho lưu trữ tài liệu đặc tả kỹ thuật, kiến trúc hệ thống, thiết kế UI/UX, biên bản họp và tệp đính kèm của dự án.
                </p>
            </div>
            <div>
                <button type="button" class="tp-btn tp-btn-primary" onclick="openDocModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 700; cursor: pointer;">
                    <i class="fa fa-plus-circle"></i>
                    <span>Thêm tài liệu mới</span>
                </button>
            </div>
        </div>

        <!-- Unified Project Sub-Nav Bar -->
        @include('projects.partials.nav', ['project' => $project, 'active' => 'documents'])

        <!-- Filter & Search Bar -->
        <div class="tp-docs-toolbar">
            <div class="tp-docs-filters">
                @foreach(['all' => 'Tất cả (' . $project->documents->count() . ')', 'google_doc' => 'Google Docs', 'notion' => 'Notion', 'confluence' => 'Confluence', 'file' => 'Tệp đính kèm', 'link' => 'Liên kết'] as $key => $label)
                    <button type="button" onclick="filterDocs('{{ $key }}')" data-filter="{{ $key }}" class="tp-doc-filter-btn {{ $key === 'all' ? 'is-active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="tp-docs-search">
                <i class="fa fa-search"></i>
                <input type="text" id="docSearchInput" oninput="searchDocs(this.value)" placeholder="Tìm tên hoặc mô tả tài liệu...">
            </div>
        </div>

        <!-- Documents Card -->
        <div class="tp-docs-card">
            @if($project->documents->isEmpty())
                <div style="text-align: center; padding: 48px 20px; color: #64748b;">
                    <i class="fa fa-folder-open-o" style="font-size: 40px; color: #94a3b8; margin-bottom: 12px; display: block;"></i>
                    <h3 style="margin: 0 0 6px 0; font-size: 17px; font-weight: 800; color: #0f172a;">Chưa có tài liệu nào trong dự án</h3>
                    <p style="margin: 0 0 18px 0; font-size: 13.5px;">Thêm liên kết đặc tả, Google Docs, Notion hoặc tải tệp lên để các thành viên cùng tra cứu.</p>
                    <button type="button" onclick="openDocModal()" style="padding: 9px 18px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 700; cursor: pointer;">
                        <i class="fa fa-plus"></i> Thêm tài liệu đầu tiên
                    </button>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="tp-docs-table" id="docsTable">
                        <thead>
                            <tr>
                                <th style="width: 54px;">Loại</th>
                                <th>Tên tài liệu</th>
                                <th>Phân loại</th>
                                <th>Mô tả tóm tắt</th>
                                <th>Người đăng</th>
                                <th>Ngày cập nhật</th>
                                <th style="width: 90px; text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->documents as $doc)
                                <tr data-type="{{ $doc->type }}" data-search="{{ mb_strtolower($doc->title . ' ' . $doc->description . ' ' . $doc->type) }}">
                                    <td>
                                        <span class="tp-doc-icon-box type-{{ $doc->type }}">
                                            @switch($doc->type)
                                                @case('google_doc') <i class="fa fa-file-word-o"></i> @break
                                                @case('notion') <span>N</span> @break
                                                @case('confluence') <i class="fa fa-book"></i> @break
                                                @case('file') <i class="fa fa-paperclip"></i> @break
                                                @default <i class="fa fa-link"></i>
                                            @endswitch
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ $doc->url }}" target="_blank" rel="noopener" style="font-weight: 700; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                            <span>{{ $doc->title }}</span>
                                            <i class="fa fa-external-link" style="font-size: 11px; opacity: 0.75;"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="tp-doc-badge">{{ $typeLabels[$doc->type] ?? ucfirst($doc->type) }}</span>
                                    </td>
                                    <td style="max-width: 320px; color: #64748b;">
                                        {{ $doc->description ?: '—' }}
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="width: 26px; height: 26px; border-radius: 50%; background: #2563eb; color: #ffffff; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center;">
                                                {{ strtoupper(mb_substr($doc->user->name ?? $doc->user->email ?? 'U', 0, 1)) }}
                                            </span>
                                            <span style="font-weight: 600;">{{ $doc->user->full_name ?? $doc->user->name ?? 'Quản trị viên' }}</span>
                                        </div>
                                    </td>
                                    <td style="color: #64748b; font-size: 12.5px;">
                                        {{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="{{ route('projects.documents.destroy', [$project, $doc]) }}" onsubmit="return confirm('Bạn có chắc muốn xóa tài liệu này?')" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Xóa tài liệu" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; cursor: pointer;">
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
</div>

<!-- Add Document Modal -->
<div class="tp-doc-modal-backdrop" id="addDocModalBackdrop" onclick="closeDocModal()"></div>
<div class="tp-doc-modal" id="addDocModal" role="dialog" aria-modal="true">
    <h2><i class="fa fa-file-text-o" style="color: #2563eb; margin-right: 8px;"></i>Thêm tài liệu dự án</h2>
    <form method="POST" action="{{ route('projects.documents.store', $project) }}" enctype="multipart/form-data">
        @csrf
        <div class="tp-doc-field">
            <label>Tên tài liệu <span style="color: #dc2626;">*</span></label>
            <input name="title" required maxlength="255" placeholder="Ví dụ: Tài liệu Đặc tả Yêu cầu & Kiến trúc Hệ thống v2.0" value="{{ old('title') }}">
        </div>

        <div class="tp-doc-field">
            <label>Phân loại tài liệu</label>
            <select name="type" id="docTypeSelect">
                <option value="google_doc">Google Docs / Google Drive</option>
                <option value="confluence">Confluence / Tài liệu kỹ thuật</option>
                <option value="notion">Notion Wiki</option>
                <option value="link">Liên kết (GitHub / Figma / Swagger...)</option>
                <option value="file">Tệp đính kèm tải lên</option>
            </select>
        </div>

        <div class="tp-doc-field">
            <label>Đường dẫn tài liệu (URL)</label>
            <input name="url" type="url" placeholder="https://docs.google.com/document/d/..." value="{{ old('url') }}">
        </div>

        <div class="tp-doc-field">
            <label>Hoặc tải lên tệp từ máy tính (PDF, DOCX, XLSX, ZIP, PNG - Tối đa 20MB)</label>
            <input name="document_file" type="file" onchange="if(this.files.length){ document.getElementById('docTypeSelect').value='file'; }">
        </div>

        <div class="tp-doc-field">
            <label>Mô tả ngắn gọn</label>
            <textarea name="description" rows="3" placeholder="Tóm tắt nội dung chính hoặc phạm vi áp dụng của tài liệu...">{{ old('description') }}</textarea>
        </div>

        <div class="tp-doc-modal-footer">
            <button type="button" onclick="closeDocModal()" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: transparent; color: #475569; font-weight: 700; cursor: pointer;">
                Hủy
            </button>
            <button type="submit" style="padding: 8px 18px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 700; cursor: pointer;">
                <i class="fa fa-check"></i> Lưu tài liệu
            </button>
        </div>
    </form>
</div>

<script>
var currentDocFilter = 'all';
var currentDocQuery = '';

function openDocModal() {
    document.getElementById('addDocModalBackdrop').classList.add('open');
    document.getElementById('addDocModal').classList.add('open');
}

function closeDocModal() {
    document.getElementById('addDocModalBackdrop').classList.remove('open');
    document.getElementById('addDocModal').classList.remove('open');
}

function applyDocFilters() {
    document.querySelectorAll('#docsTable tbody tr').forEach(function (row) {
        var matchType = (currentDocFilter === 'all' || row.getAttribute('data-type') === currentDocFilter);
        var searchHaystack = row.getAttribute('data-search') || '';
        var matchQuery = (!currentDocQuery || searchHaystack.indexOf(currentDocQuery) !== -1);
        row.style.display = (matchType && matchQuery) ? '' : 'none';
    });
}

function filterDocs(type) {
    currentDocFilter = type;
    document.querySelectorAll('.tp-doc-filter-btn').forEach(function (btn) {
        if (btn.getAttribute('data-filter') === type) {
            btn.classList.add('is-active');
        } else {
            btn.classList.remove('is-active');
        }
    });
    applyDocFilters();
}

function searchDocs(query) {
    currentDocQuery = (query || '').toLowerCase().trim();
    applyDocFilters();
}
</script>
@endsection
