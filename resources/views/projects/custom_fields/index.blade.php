@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/project-dashboard-luxury.css') }}?v={{ file_exists(public_path('css/project-dashboard-luxury.css')) ? filemtime(public_path('css/project-dashboard-luxury.css')) : time() }}" rel="stylesheet">
<style>
    .cf-container {
        font-family: inherit;
        color: #1e293b;
    }
    .cf-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .cf-card-header {
        padding: 16px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .cf-card-body {
        padding: 20px;
    }
    .cf-badge-type {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .cf-key-code {
        font-family: monospace;
        background: #f1f5f9;
        color: #0f172a;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="col-xs-12 col-md-12 p-0 cf-container">
    <!-- Project Header -->
    <div class="pd-header mb-3" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div class="pd-header-left">
            <div class="pd-breadcrumb" style="margin-bottom: 6px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <a href="{{ route('projects.index') }}" style="color: #64748b; text-decoration: none;">Dự án</a>
                <span>/</span>
                <a href="{{ route('projects.show', $project) }}" style="color: #64748b; text-decoration: none;">{{ $project->name }}</a>
                <span>/</span>
                <span style="color: #0f172a; font-weight: 700;">Trường tùy chỉnh</span>
            </div>
            <div class="pd-title-row" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <h1 class="pd-title" style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">
                    <i class="fa fa-sliders text-primary me-2"></i>Tùy biến Trường Dữ liệu (Custom Fields)
                </h1>
                <span class="pd-project-key">{{ $project->project_code ?? 'PROJ' }}</span>
            </div>
            <p style="margin: 6px 0 0 0; color: #64748b; font-size: 13px;">
                Tự do định nghĩa các trường dữ liệu đặc thù cho công việc (Môi trường, Phiên bản OS, Độ nghiêm trọng, Tiêu chí...). Có thể lọc bằng <strong>TQL Search</strong> với cú pháp: <code>cf[field_key] = "giá trị"</code>.
            </p>
        </div>
    </div>

    <!-- Unified Project Sub-Nav Bar -->
    @include('projects.partials.nav', ['project' => $project, 'active' => 'custom_fields'])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Left: Custom Fields Table -->
        <div class="col-md-8 col-sm-12">
            <div class="cf-card">
                <div class="cf-card-header">
                    <h2 style="font-size: 16px; font-weight: 700; margin: 0;">
                        <i class="fa fa-list-alt text-primary me-2"></i>Danh sách trường hiện có ({{ $customFields->count() }})
                    </h2>
                </div>
                <div class="cf-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Tên trường</th>
                                    <th>Mã truy vấn (Field Key)</th>
                                    <th>Kiểu dữ liệu</th>
                                    <th>Lựa chọn / Định dạng</th>
                                    <th>Bắt buộc</th>
                                    <th class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customFields as $field)
                                    <tr>
                                        <td>
                                            <strong class="text-dark">{{ $field->name }}</strong>
                                            @if($field->description)
                                                <div class="text-muted small">{{ $field->description }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="cf-key-code">cf[{{ $field->field_key }}]</span>
                                        </td>
                                        <td>
                                            <span class="cf-badge-type">
                                                <i class="fa {{ $availableTypes[$field->field_type]['icon'] ?? 'fa-tag' }}"></i>
                                                {{ $availableTypes[$field->field_type]['label'] ?? $field->field_type }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($field->field_type === 'select' && !empty($field->options))
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($field->options as $opt)
                                                        <span class="badge bg-light text-dark border">{{ $opt }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($field->is_required)
                                                <span class="badge bg-danger">Bắt buộc</span>
                                            @else
                                                <span class="badge bg-light text-muted">Tùy chọn</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <form action="{{ route('projects.custom-fields.destroy', [$project, $field]) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa trường tùy chỉnh này? Mọi giá trị đã nhập trên các task sẽ bị xóa vĩnh viễn.');" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa trường">
                                                    <i class="fa fa-trash-o"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fa fa-sliders fa-2x mb-2 d-block opacity-50"></i>
                                            Dự án này chưa có trường tùy chỉnh nào. Hãy thêm trường mới ở form bên phải!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Add Custom Field Form -->
        <div class="col-md-4 col-sm-12">
            <div class="cf-card">
                <div class="cf-card-header">
                    <h2 style="font-size: 16px; font-weight: 700; margin: 0;">
                        <i class="fa fa-plus-circle text-success me-2"></i>Thêm trường tùy chỉnh mới
                    </h2>
                </div>
                <div class="cf-card-body">
                    <form action="{{ route('projects.custom-fields.store', $project) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Tên hiển thị <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="VD: Môi trường (Environment)" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Mã truy vấn (Field Key)</label>
                            <input type="text" name="field_key" class="form-control form-control-sm" placeholder="VD: environment (để trống tự tạo)">
                            <small class="text-muted d-block mt-1">Dùng để viết câu truy vấn TQL, ví dụ: <code>cf[environment] = "Production"</code></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Kiểu dữ liệu <span class="text-danger">*</span></label>
                            <select name="field_type" id="field-type-select" class="form-select form-select-sm" required onchange="toggleOptionsBox(this.value)">
                                @foreach($availableTypes as $typeKey => $meta)
                                    <option value="{{ $typeKey }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="options-box-wrap" style="display: none;">
                            <label class="form-label fw-bold small text-muted">Danh sách lựa chọn (Mỗi mục 1 dòng hoặc ngăn cách bởi dấu phẩy)</label>
                            <textarea name="options" class="form-control form-control-sm" rows="3" placeholder="Production&#10;Staging&#10;Development&#10;Testing"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Mô tả / Hướng dẫn điền</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Nhập ghi chú ngắn..."></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_required" value="1" id="is_required_check">
                                <label class="form-check-label small" for="is_required_check">
                                    Bắt buộc điền khi tạo hoặc chỉnh sửa task
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                            <i class="fa fa-save me-1"></i>Lưu trường tùy chỉnh
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleOptionsBox(type) {
    const wrap = document.getElementById('options-box-wrap');
    if (type === 'select') {
        wrap.style.display = 'block';
    } else {
        wrap.style.display = 'none';
    }
}
</script>
@endpush
@endsection

